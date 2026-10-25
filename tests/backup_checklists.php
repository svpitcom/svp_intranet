<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/regression.php';
$start=$checks;
Database::$db=new TestPDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db=Database::$db;
$db->exec('CREATE TABLE backup_checklists(id INTEGER PRIMARY KEY AUTOINCREMENT,month TEXT,location TEXT,server_name TEXT,payload TEXT,version INTEGER,updated_by INTEGER,updated_at TEXT,UNIQUE(month,location,server_name))');
$db->exec('CREATE TABLE backup_checklist_history(id INTEGER PRIMARY KEY AUTOINCREMENT,form_id INTEGER,payload TEXT,version INTEGER,actor_id INTEGER,created_at TEXT,UNIQUE(form_id,version))');
$admin=['svp_user_id'=>1,'user_role'=>'admin','svp_department_id'=>6,'is_active'=>1];
$it=['svp_user_id'=>2,'user_role'=>'user','svp_department_id'=>2,'is_active'=>1];
$other=['svp_user_id'=>3,'user_role'=>'manager','svp_department_id'=>6,'is_active'=>1];
function rejectBackup(callable $fn,string $label): void { try { $fn(); } catch (RuntimeException $e) { check(true,$label); return; } check(false,$label); }
$input=['month'=>'2026-10','location'=>'SVP','server_name'=>'SVP-FILE Server on Cloud','prepared_by'=>'ผู้จัดทำทดสอบ','approved_by'=>'ผู้อนุมัติทดสอบ','prepared_date'=>'2026-10-24','approved_date'=>'','days'=>[1=>['data'=>'0','data_full'=>'=1+1','start'=>'23:30:15','end'=>'00:45:30','status'=>'Success','checked_by'=>'เจ้าหน้าที่ IT','notes'=>'ทดสอบ <script> & ข้อความภาษาไทย'],31=>['status'=>'Failed','notes'=>'ตรวจสอบพื้นที่ปลายทาง']]];
$data=BackupChecklist::clean($input);
check(count($data['days'])===31 && $data['days'][2]['status']==='','blank dates never assumed successful');
check($data['days'][1]['data']==='0' && $data['days'][1]['data_full']==='=1+1','literal zero and text retained');
foreach(['2024-02'=>29,'2026-02'=>28,'2026-04'=>30] as $month=>$days) check(count(BackupChecklist::clean(array_replace($input,['month'=>$month,'days'=>[]]))['days'])===$days,'calendar '.$month);
foreach(['2026-13','26-10','2026-1','1999-12',[],null] as $month) rejectBackup(fn()=>BackupChecklist::clean(array_replace($input,['month'=>$month])),'invalid month');
foreach([['days'=>[32=>[]]],['days'=>[0=>[]]],['month'=>'2026-02','days'=>[29=>[]]],['days'=>'bad'],['days'=>[1=>'bad']],['days'=>[1=>['start'=>'24:00']]],['days'=>[1=>['end'=>'12:60']]],['days'=>[1=>['status'=>[]]]],['prepared_date'=>'2026-02-30'],['server_name'=>''],['days'=>[1=>['notes'=>str_repeat('ก',501)]]]] as $change) rejectBackup(fn()=>BackupChecklist::clean(array_replace($input,$change)),'invalid row/header');
$model=new BackupChecklist();
rejectBackup(fn()=>$model->save(null,$other,$input),'non IT cannot write');
rejectBackup(fn()=>$model->listing($other,'',1),'non IT cannot list');
$id=$model->save(null,$it,$input); $row=$model->detail($id,$admin);
check((int)$row['version']===1 && $row['data']===$data,'saved complete snapshot');
rejectBackup(fn()=>$model->detail($id,$other),'non IT cannot read');
rejectBackup(fn()=>$model->save(null,$admin,$input),'duplicate month/server refused');
$edited=array_replace($input,['version'=>'1','days'=>array_replace($input['days'],[2=>['status'=>'Warning','checked_by'=>'ผู้ตรวจสอบ']])]);
$model->save($id,$admin,$edited);
check((int)$model->find($id)['version']===2 && (int)$db->query('SELECT COUNT(*) FROM backup_checklist_history')->fetchColumn()===2,'edit increments snapshot history');
$before=$model->find($id);
rejectBackup(fn()=>$model->save($id,$it,$edited),'stale write refused');
rejectBackup(fn()=>$model->save($id,$it,array_replace($edited,['month'=>'2026-12','version'=>'2'])),'saved month cannot change');
check($before===$model->find($id),'rejections leave data intact');
$db->exec("CREATE TRIGGER history_fail BEFORE INSERT ON backup_checklist_history BEGIN SELECT RAISE(ABORT,'simulated history failure'); END");
rejectBackup(fn()=>$model->save($id,$it,array_replace($edited,['version'=>'2','prepared_by'=>'must rollback'])),'history failure aborts transaction');
check($before===$model->find($id),'failed history rolls back form update');
$db->exec('DROP TRIGGER history_fail');

class BackupViewHarness extends BackupChecklistController
{
    protected function view(string $view,array $data=[],string $layout='main'): void { extract($data); require BASE_PATH.'/app/Views/'.$view.'.php'; }
    protected function redirect(string $path): void { throw new RedirectSignal($path); }
}
$controller=new BackupViewHarness();
$_SESSION['user']=$it; $_GET=[]; $_POST=[];
ob_start(); $controller->edit((string)$id); $html=ob_get_clean();
check(substr_count($html,'name="days[')===31*7,'all seven fields on every day');
check(str_contains($html,'&lt;script&gt;') && !str_contains($html,'<script> &'),'HTML escapes entries');
check(str_contains($html,'Export Excel') && str_contains($html,'Export PDF'),'saved export controls');
$_GET=['month'=>'2024-02']; ob_start(); $controller->edit(); $newHtml=ob_get_clean();
check(substr_count($newHtml,'name="days[')===29*7 && !str_contains($newHtml,'Export Excel'),'new leap month has correct rows and no export');
$_GET=[]; $_POST=$edited; ob_start(); $controller->save((string)$id); $conflictHtml=ob_get_clean();
check(http_response_code()===422 && str_contains($conflictHtml,'name="version" value="1"'),'stale redisplay preserves old version'); http_response_code(200);
check($model->find($id)===$before,'controller conflict did not mutate');
$_POST=array_replace($edited,['version'=>'2','days'=>[1=>['notes'=>'<img src=x onerror=alert(1)>','start'=>'25:00']]]);
ob_start(); $controller->save((string)$id); $badHtml=ob_get_clean();
check(http_response_code()===422 && str_contains($badHtml,'&lt;img src=x'),'validation redisplay escapes invalid input'); http_response_code(200);
foreach([$other,array_replace($it,['is_active'=>0]),array_replace($it,['svp_department_id'=>13])] as $denied) {
    $_SESSION['user']=$denied;
    foreach(['index','edit','save','export'] as $action) {
        ob_start(); $controller->$action((string)$id); ob_end_clean();
        check(http_response_code()===403,'access enforced on '.$action); http_response_code(200);
    }
    check(!str_contains(render('partials/sidebar',[]),'/it-backup'),'menu hidden without IT access');
}
$_SESSION['user']=$it; $_POST=[]; $_GET=[];
check(str_contains(render('partials/sidebar',[]),'/it-backup'),'menu available to IT');
ob_start(); $controller->edit('99999'); ob_end_clean(); check(http_response_code()===404,'missing form not found'); http_response_code(200);
$_GET=['format'=>'exe']; ob_start(); $controller->export((string)$id); ob_end_clean(); check(http_response_code()===400,'unknown export format denied'); http_response_code(200); $_GET=[];
$routes=require BASE_PATH.'/app/Config/routes.php';
foreach($routes as $route=>$handler) if(str_contains($route,'/it-backup')) check(in_array('auth',$handler[2],true),'authenticated route '.$route);
$_POST=['_csrf'=>'invalid']; ob_start(); (new Router(['POST /it-backup/{id}'=>['BackupChecklistController','save',[]]]))->dispatch('POST','/it-backup/'.$id); ob_end_clean();
check(http_response_code()===403 && $model->find($id)===$before,'CSRF rejects before saving'); http_response_code(200); $_POST=[];
$_POST=array_replace($input,['month'=>'2026-12']);
try { $controller->save(); check(false,'successful controller save redirects'); }
catch (RedirectSignal $redirect) {
    $savedId=(int)basename($redirect->getMessage());
    check($model->detail($savedId,$it)['data']['month']==='2026-12','controller saves and redirects to persisted document');
    check(str_contains(Session::flash('success'),'IT-F-004'),'successful controller save shows confirmation');
}
$_POST=[];

$hash=hash_file('sha256',BASE_PATH.'/app/Templates/it-backup-checklist.xlsx');
$bytes=BackupChecklistExport::xlsx($data);
$tmp=tempnam(sys_get_temp_dir(),'backup-test-'); $zipPath=$tmp.'.zip'; file_put_contents($zipPath,$bytes); $zip=new PharData($zipPath);
$xml=new DOMDocument(); check($xml->loadXML($zip['xl/worksheets/sheet1.xml']->getContent()),'valid worksheet XML');
$xp=new DOMXPath($xml); $xp->registerNamespace('x','http://schemas.openxmlformats.org/spreadsheetml/2006/main');
$cell=static fn($ref)=>$xp->query('//x:c[@r="'.$ref.'"]')->item(0);
check($cell('A6')->getAttribute('t')==='n' && $xp->query('//x:c[@r="A6"]/x:v')->item(0)->textContent==='46296','Excel date numeric');
check(abs((float)$xp->query('//x:c[@r="D6"]/x:v')->item(0)->textContent-(23*3600+30*60+15)/86400)<0.000000001,'Excel time numeric with seconds');
check($cell('C6')->getAttribute('t')==='inlineStr' && $xp->query('//x:c[@r="C6"]/x:f')->length===0 && str_contains($cell('C6')->textContent,'=1+1'),'formula-like input stays literal');
check($cell('F7')->textContent==='' && $cell('G6')->textContent==='เจ้าหน้าที่ IT','empty status and Thai checker map correctly');
check($xp->query('//x:mergeCell')->length===14 && $cell('A39')->getAttribute('t')==='s','original merges and revision preserved');
unset($zip); unlink($zipPath); unlink($tmp);
check(hash_file('sha256',BASE_PATH.'/app/Templates/it-backup-checklist.xlsx')===$hash,'template unchanged');
require_once BASE_PATH.'/vendor/autoload.php';
$pdf=BackupChecklistExport::pdf($data); check(str_starts_with($pdf,'%PDF-'),'PDF generated');
$short=BackupChecklist::clean(array_replace($input,['month'=>'2026-02','days'=>[]]));
$shortBytes=BackupChecklistExport::xlsx($short);
$tmp=tempnam(sys_get_temp_dir(),'backup-short-');$path=$tmp.'.zip';file_put_contents($path,$shortBytes);$zip=new PharData($path);
$doc=new DOMDocument();$doc->loadXML($zip['xl/worksheets/sheet1.xml']->getContent());$q=new DOMXPath($doc);$q->registerNamespace('x','http://schemas.openxmlformats.org/spreadsheetml/2006/main');
check($q->query('//x:c[@r="A33"]/x:v')->length===1 && $q->query('//x:c[@r="A34"]/x:is/x:t')->item(0)->textContent==='','February unused dates remain blank');
unset($zip);unlink($path);unlink($tmp);
for($i=0;$i<16;$i++) $model->save(null,$it,array_replace($input,['server_name'=>'TEST-'.$i]));
$listing=$model->listing($it,'2026-10',2);
check($listing['paging']['total']===17 && count($listing['forms'])===2,'pagination and month filter');
check($model->listing($it,'2026-09',1)['paging']['total']===0,'other months excluded');
if(isset($argv[1]) && is_dir($argv[1])) {
    file_put_contents($argv[1].'/backup-test.xlsx',$bytes); file_put_contents($argv[1].'/backup-test.pdf',$pdf);
    $long=$data; $long['days'][1]['notes']=str_repeat('ทดสอบข้อความยาว ',20); $long['days'][1]['checked_by']=str_repeat('ชื่อผู้ตรวจ ',8);
    file_put_contents($argv[1].'/backup-long.pdf',BackupChecklistExport::pdf($long));
    $css=file_get_contents(BASE_PATH.'/public/assets/vendor/bootstrap/css/bootstrap.min.css').file_get_contents(BASE_PATH.'/public/assets/css/style.css').file_get_contents(BASE_PATH.'/public/assets/css/backup-checklist.css');
    $html=preg_replace('~<link rel="stylesheet"[^>]+>~','',$html);
    file_put_contents($argv[1].'/backup-form-preview.html','<!doctype html><html lang="th"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>IT-F-004 Form Preview</title><style>'.$css.'</style><body><main style="padding:24px;max-width:1700px;margin:auto">'.$html.'</main></body></html>');
}
echo 'PASS IT backup checklist: '.($checks-$start)." checks (isolated SQLite; no production records)\n";
