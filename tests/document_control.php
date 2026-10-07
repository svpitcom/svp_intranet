<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/regression.php';
$start=$checks;
$db=new TestPDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
Database::$db=$db;
$db->exec('CREATE TABLE controlled_documents (id INTEGER PRIMARY KEY AUTOINCREMENT,code TEXT UNIQUE,title TEXT,revision TEXT,department_id INTEGER,status TEXT,effective_date TEXT,review_date TEXT,file_item_id TEXT,file_name TEXT,file_url TEXT,notes TEXT,version INTEGER,updated_by INTEGER,created_at TEXT,updated_at TEXT); CREATE TABLE controlled_document_history (id INTEGER PRIMARY KEY AUTOINCREMENT,document_id INTEGER,snapshot TEXT,changed_by INTEGER,changed_at TEXT); CREATE TABLE department (svp_department_id INTEGER,svp_department_name TEXT); CREATE TABLE users (svp_user_id INTEGER,first_name TEXT,last_name TEXT)');
$data=['code'=>'DCC-001','title'=>'<script>alert(1)</script>','revision'=>'00','department_id'=>null,'status'=>'draft','effective_date'=>null,'review_date'=>null,'file_item_id'=>'','file_name'=>'','file_url'=>'','notes'=>'Initial'];
check(DocumentControlInput::validate($data)===[],'draft can be saved without file');
$bad=$data; $bad['status']='active';
check(count(DocumentControlInput::validate($bad))>0,'active requires file and effective date');
$bad=$data; $bad['effective_date']='2026-02-30';
check(count(DocumentControlInput::validate($bad))>0,'invalid calendar date rejected');
check(!DocumentControlInput::safeUrl('javascript:alert(1)'),'unsafe file URL rejected');
$model=new ControlledDocument(); $id=$model->saveDocument(null,$data,0,1);
check(count($model->history($id))===1,'create saves first snapshot');
$data['revision']='01'; $model->saveDocument($id,$data,1,1);
check((int)$model->find($id)['version']===2 && count($model->history($id))===2,'update increments version and adds snapshot');
try { $model->saveDocument($id,$data,1,1); check(false,'stale edit must fail'); }
catch(RuntimeException $e) { check(count($model->history($id))===2,'stale edit leaves data and history unchanged'); }
try { $model->saveDocument(null,$data,0,1); check(false,'duplicate code must fail'); }
catch(RuntimeException $e) { check((int)$db->query('SELECT COUNT(*) FROM controlled_documents')->fetchColumn()===1,'duplicate code does not create record'); }
$db->exec("CREATE TRIGGER fail_history BEFORE INSERT ON controlled_document_history BEGIN SELECT RAISE(ABORT, 'test failure'); END");
$data['revision']='02';
try { $model->saveDocument($id,$data,2,1); check(false,'history failure must rollback'); }
catch(RuntimeException $e) { check($model->find($id)['revision']==='01','snapshot failure rolls back current document'); }
$list=$model->listing('','',1);
$html=render('document_control/index',$list+['q'=>'','status'=>'']);
check(str_contains($html,'&lt;script&gt;') && !str_contains($html,'<script>alert'),'document title escaped');
$html=render('document_control/form',['document'=>$model->find($id),'id'=>$id,'error'=>'','departments'=>[],'history'=>$model->history($id)]);
check(str_contains($html,'name="_csrf"') && str_contains($html,'name="version"'),'edit form includes CSRF and concurrency version');
foreach(require BASE_PATH.'/app/Config/routes.php' as $route=>$handler) if(str_contains($route,'/document-control')) check($handler[2]===['auth','documentControl'],'DCC route requires authenticated department policy');
$calls=[]; $config=['tenant_id'=>'test.example','client_id'=>'test','client_secret'=>'test','site_id'=>'site','drive_id'=>'drive','department_folders'=>['DCC'=>'dcc']];
$client=new SharePointClient($config,static function($method,$url,$headers,$body) use (&$calls) {
    $calls[]=$url;
    if($method==='POST') return ['status'=>200,'body'=>'{"access_token":"test","expires_in":3600}'];
    $id=str_contains($url,'/items/file?')?'file':(str_contains($url,'/items/dcc?')?'dcc':(str_contains($url,'/items/sub?')?'sub':'outside'));
    $item=['id'=>$id,'parentReference'=>['driveId'=>'drive']];
    if($id==='file') { $item['file']=new stdClass(); $item['parentReference']['id']='sub'; }
    elseif($id==='sub') { $item['folder']=new stdClass(); $item['parentReference']['id']='dcc'; }
    else $item['folder']=new stdClass();
    return ['status'=>200,'body'=>json_encode($item)];
});
check($client->dccItem('file')['id']==='file','nested DCC file allowed');
try { $client->dccItem('outside'); check(false,'outside DCC rejected'); }
catch(RuntimeException $e) { check(true,'outside DCC rejected'); }
echo 'PASS: '.($checks-$start)." Document Control checks (isolated database and mocked Graph)\n";
