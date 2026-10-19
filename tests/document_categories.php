<?php
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/document_control.php';
$start=$checks;
$db->exec('DROP TRIGGER fail_history');
$data['code']='IT-TEST'; $data['category']='IT'; $data['revision']='01';
$model->saveDocument(null,$data,0,1);
$data['code']='QC-TEST'; $data['category']='QC'; $model->saveDocument(null,$data,0,1);
$list=$model->listing('TEST','draft',1,'IT');
check($list['total']===1 && $list['rows'][0]['code']==='IT-TEST','category search and status combine');
check($list['counts']['draft']===1,'status count scoped to category');
check($list['categoryCounts']['IT']===1 && $list['categoryCounts']['QC']===1,'category counts include all categories');
check($model->listing('','',1,'UNSORTED')['total']===1,'uncategorized filter');
$bad=$data; $bad['category']='INVALID'; check(count(DocumentControlInput::validate($bad))>0,'invalid category rejected');
check(DocumentCategory::infer(['code'=>'IT-F-001'])==='IT','existing code inference');
check(DocumentCategory::infer(['code'=>'IT-F-001','notes'=>"ชีต: QC แถว: 20"])==='QC','source sheet takes priority');
check(DocumentCategory::infer(['code'=>'UNKNOWN-1'])==='','unknown stays uncategorized');
$list['pages']=2;
$html=render('document_control/index',$list+['q'=>'TEST','status'=>'draft','category'=>'IT']);
check(str_contains($html,'category=IT') && str_contains($html,'category=MASTER')===false,'category preserved in selected view');
check(str_contains($html,'19.รายการรายชื่อบันทึก'),'special categories displayed');
$calls=[];
$client=new SharePointClient(['tenant_id'=>'test','client_id'=>'test','client_secret'=>'test','site_id'=>'s','drive_id'=>'drive','department_folders'=>['DCC'=>'dcc']],static function($method,$url,$headers,$body) use (&$calls) {
    $calls[]=$url;
    if($method==='POST') return ['status'=>200,'body'=>'{"access_token":"test","expires_in":3600}'];
    if(str_contains($url,'/children')) {
        $items=str_contains($url,'/items/dcc/') ? [['id'=>'root','name'=>'0. Document control','folder'=>[]]] : [['id'=>'it','name'=>'10.IT','folder'=>[]]];
        return ['status'=>200,'body'=>json_encode(['value'=>$items])];
    }
    $isRoot=str_contains($url,'/items/root?');
    return ['status'=>200,'body'=>json_encode(['id'=>$isRoot?'root':'dcc','folder'=>[],'parentReference'=>['driveId'=>'drive','id'=>'dcc']])];
});
check(DocumentCategory::folder($client,'IT')==='it','category folder resolved under DCC');
try { DocumentCategory::folder($client,'INVALID'); check(false,'invalid folder category'); } catch(InvalidArgumentException $e) { check(true,'invalid folder category rejected'); }
echo 'PASS: '.($checks-$start)." category checks\n";
