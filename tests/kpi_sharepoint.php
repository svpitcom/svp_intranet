<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
// Isolated SQLite and mock HTTP only. Never load production configuration.
require __DIR__.'/kpi_forms.php';
$start=$checks;
$config=['tenant_id'=>'test.example','client_id'=>'app','client_secret'=>'test-secret','site_id'=>'site','drive_id'=>'drive','export_folder_id'=>'folder','department_folders'=>['IT'=>'it-folder','DCC'=>'dcc-folder','EMPTY'=>'']];
$calls=[]; $mode='ok';
$client=new SharePointClient($config,static function($method,$url,$headers,$body) use (&$calls,&$mode) {
    $calls[]=compact('method','url','headers','body');
    if ($method==='POST') return ['status'=>200,'body'=>'{"access_token":"test-token","expires_in":3600}'];
    if ($method==='GET') {
        $folder=rawurldecode(basename(parse_url($url,PHP_URL_PATH)));
        return ['status'=>200,'body'=>json_encode(['id'=>$folder,'folder'=>new stdClass(),'parentReference'=>['driveId'=>$mode==='wrong-drive'?'other-drive':'drive']])];
    }
    if ($method!=='PUT') throw new LogicException('Unexpected HTTP method');
    if ($mode==='fail') return ['status'=>403,'body'=>'{}'];
    preg_match('~:/([^/]+):/content$~',$url,$matches);
    $name=rawurldecode($matches[1]);
    return ['status'=>201,'body'=>json_encode(['id'=>$mode==='missing-id'?'':'uploaded','name'=>$mode==='wrong-name'?'other.xlsx':$name,'webUrl'=>$mode==='unsafe-url'?'javascript:alert(1)':'https://example.sharepoint.com/sites/test/'.$name])];
});
check(array_keys($client->exportDestinations())===['default','IT','DCC'],'only configured destination labels exposed');
check((new SharePointClient([]))->exportDestinations()===[],'missing credentials disables destinations');
check((new SharePointClient(array_replace($config,['export_folder_id'=>''])))->exportDestinations()===['IT'=>'IT','DCC'=>'DCC'],'department destinations work without default');
$current=$form->detail($id,$admin);
$xlsx=KpiFormExport::xlsx($current['data']);
$file=$client->uploadKpiForm($id,2026,2,'xlsx',$xlsx,'IT');
$first=end($calls);
check($first['method']==='PUT' && $first['body']===$xlsx,'exact Excel bytes uploaded');
check(in_array('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',$first['headers'],true),'Excel MIME');
check(str_contains($first['url'],'/items/it-folder:/MR-F-027-01-2026-'.$id.'-v2.xlsx:/content'),'server folder and document version in upload path');
check($file['name']==='MR-F-027-01-2026-'.$id.'-v2.xlsx' && str_starts_with($file['webUrl'],'https://'),'confirmed response and HTTPS link');
$client->uploadKpiForm($id,2026,2,'xlsx',$xlsx,'IT');
check(end($calls)['url']===$first['url'],'retry targets same file');
$client->uploadKpiForm($id,2026,3,'xlsx',$xlsx,'IT');
check(end($calls)['url']!==$first['url'],'new version uses separate file');
$client->uploadKpiForm($id,2026,2,'pdf',$pdf,'default');
check(end($calls)['body']===$pdf && in_array('Content-Type: application/pdf',end($calls)['headers'],true),'PDF bytes and MIME');
check(str_contains(end($calls)['url'],'/items/folder:'),'default configured folder');
foreach ([['xlsx',$xlsx,'other'],['exe',$xlsx,'IT'],['pdf',$xlsx,'IT'],['xlsx',str_repeat('x',10*1024*1024+1),'IT']] as [$format,$payload,$target]) {
    $before=count($calls);
    rejectsKpi(fn()=>$client->uploadKpiForm($id,2026,2,$format,$payload,$target),'invalid upload refused');
    check(count($calls)===$before,'invalid upload makes no network request');
}
foreach (['IT','default'] as $target) {
    $mode='wrong-drive'; $before=count($calls);
    rejectsKpi(fn()=>$client->uploadKpiForm($id,2026,2,'xlsx',$xlsx,$target),'folder from another library refused');
    check(!in_array('PUT',array_column(array_slice($calls,$before),'method'),true),'wrong library never receives upload');
}
foreach (['missing-id','wrong-name'] as $mode) rejectsKpi(fn()=>$client->uploadKpiForm($id,2026,2,'xlsx',$xlsx,'IT'),'unconfirmed result rejected');
$mode='unsafe-url';
check($client->uploadKpiForm($id,2026,2,'xlsx',$xlsx,'IT')['webUrl']==='','unsafe result link suppressed');
$mode='ok';

class KpiSharePointHarness extends KpiFormViewHarness
{
    public SharePointClient $client;
    protected function sharePointClient(): SharePointClient { return $this->client; }
    protected function redirect(string $path): void { throw new RedirectSignal($path); }
}
$controller=new KpiSharePointHarness(); $controller->client=$client;
$send=static function(array $post) use ($controller,$id): void {
    $_GET=[]; $_POST=$post; unset($_SESSION['_flash']);
    try { $controller->sendSharePoint((string)$id); }
    catch (RedirectSignal $e) { check($e->getMessage()==='/kpi/forms/'.$id,'returns to saved form'); }
};
$post=['format'=>'xlsx','destination'=>'IT','version'=>'2'];
$snapshot=$form->find($id); $history=$db->query('SELECT * FROM kpi_form_history')->fetchAll();
$_SESSION['user']=$manager; $before=count($calls);
ob_start(); $send($post); ob_end_clean();
check(http_response_code()===403 && count($calls)===$before,'manager direct send forbidden'); http_response_code(200);
$_SESSION['user']=array_replace($admin,['is_active'=>0]);
ob_start(); $send($post); ob_end_clean();
check(http_response_code()===403 && count($calls)===$before,'inactive admin forbidden'); http_response_code(200);
$_SESSION['user']=$admin;
$send(array_replace($post,['version'=>'1']));
check(count($calls)===$before && Session::flash('error')!==null,'stale form cannot upload');
$send(array_replace($post,['destination'=>'https://attacker.example']));
check(count($calls)===$before && count((new KpiFormDelivery())->recent($id))===0,'arbitrary destination refused without receipt');
$send($post);
$receipts=(new KpiFormDelivery())->recent($id);
check(count($receipts)===1 && (int)$receipts[0]['version']===2 && $receipts[0]['format']==='xlsx','successful send stores exact version');
check((int)$db->query('SELECT sent_by FROM kpi_form_deliveries')->fetchColumn()===1,'sender retained');
check(str_contains(Session::flash('success'),'ฉบับที่ 2'),'success describes version');
$mode='fail'; $send($post);
check(count((new KpiFormDelivery())->recent($id))===1 && str_contains(Session::flash('error'),'ไม่ต้องบันทึกแบบฟอร์มใหม่'),'remote failure retains receipt count and explains retry');
check($form->find($id)===$snapshot && $db->query('SELECT * FROM kpi_form_history')->fetchAll()===$history,'sending and failure never alter saved form or history');
$mode='ok'; $send(array_replace($post,['format'=>'pdf','destination'=>'default']));
check((new KpiFormDelivery())->recent($id)[0]['format']==='pdf','PDF delivery receipt');

ob_start(); $controller->edit((string)$id); $adminHtml=ob_get_clean();
check(str_contains($adminHtml,'name="destination"') && str_contains($adminHtml,'เปิดใน SharePoint'),'admin send control and uploaded link');
check(!str_contains($adminHtml,'test-secret') && !str_contains($adminHtml,'it-folder'),'no credentials or folder IDs in HTML');
$xmlErrors=libxml_use_internal_errors(true);
$dom=new DOMDocument(); $dom->loadHTML('<?xml encoding="UTF-8">'.$adminHtml); $xpath=new DOMXPath($dom);
libxml_clear_errors(); libxml_use_internal_errors($xmlErrors);
check($xpath->query('//form')->length===2 && $xpath->query('//form//form')->length===0,'send and edit are separate forms');
check($xpath->query('//form[contains(@action,"/sharepoint")]/input[@name="_csrf"]')->length===1,'send carries CSRF token');
$_SESSION['user']=$manager; ob_start(); $controller->edit((string)$id); $managerHtml=ob_get_clean();
check(!str_contains($managerHtml,'name="destination"') && str_contains($managerHtml,'เปิดใน SharePoint'),'authorized reader sees history without sending');
$_SESSION['user']=$other; ob_start(); $controller->edit((string)$id); $otherHtml=ob_get_clean();
check(http_response_code()===403 && !str_contains($otherHtml,'example.sharepoint.com'),'other department cannot view delivery links'); http_response_code(200);
$_SESSION['user']=$admin;
ob_start(); $controller->edit(); $newHtml=ob_get_clean();
check(!str_contains($newHtml,'name="destination"'),'no send control before first save');
$controller->client=new SharePointClient([]);
ob_start(); $controller->edit((string)$id); $unconfiguredHtml=ob_get_clean();
check(str_contains($unconfiguredHtml,'ยังไม่มีปลายทางพร้อมใช้งาน') && str_contains($unconfiguredHtml,'class="btn btn-primary" disabled'),'unconfigured send disabled');
$controller->client=$client;
$routes=require BASE_PATH.'/app/Config/routes.php';
check($routes['POST /kpi/forms/{id}/sharepoint'][2]===['auth','role:admin'],'authenticated admin POST route');
// Simulate local audit failure after a confirmed remote upload.
$db->exec('DROP TABLE kpi_form_deliveries');
$send($post);
check(str_contains(Session::flash('error'),'SharePoint รับไฟล์') && $form->find($id)===$snapshot,'remote success distinguished from local history failure');
if (isset($argv[1]) && is_dir($argv[1])) {
    $css=file_get_contents(BASE_PATH.'/public/assets/vendor/bootstrap/css/bootstrap.min.css');
    $css.="\n".file_get_contents(BASE_PATH.'/public/assets/css/style.css');
    file_put_contents($argv[1].'/kpi-sharepoint-preview.html','<!doctype html><html lang="th"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>KPI SharePoint Preview</title><style>'.$css.'</style><body><main style="padding:24px;max-width:1700px;margin:auto">'.$adminHtml.'</main></body></html>');
}
echo 'PASS KPI SharePoint: '.($checks-$start)." checks (mock HTTP; no remote uploads)\n";
