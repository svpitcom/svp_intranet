<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/regression.php';
$start = $checks;
$config = ['tenant_id'=>'example.com','client_id'=>'app','client_secret'=>'test','site_id'=>'site','drive_id'=>'drive','export_folder_id'=>'original','department_folders'=>['IT'=>'it-folder','DCC'=>'dcc-folder']];
$calls=[]; $wrongDrive=false;
$client = new SharePointClient($config, static function($method,$url,$headers,$body) use (&$calls,&$wrongDrive) {
    $calls[] = [$method,$url];
    if ($method==='POST') return ['status'=>200,'body'=>'{"access_token":"test","expires_in":3600}'];
    if ($method==='GET') return ['status'=>200,'body'=>json_encode(['id'=>'it-folder','folder'=>new stdClass(),'parentReference'=>['driveId'=>$wrongDrive?'other':'drive']])];
    return ['status'=>200,'body'=>'{"id":"saved"}'];
});
$it=$client->forDepartment('IT');
$it->exportTable('departments',[]);
check(str_contains(end($calls)[1],'/items/it-folder:/'),'selected destination receives export');
$client->exportTable('departments',[]);
check(str_contains(end($calls)[1],'/items/original:/'),'original client retains original destination');
$count=count($calls);
try { $client->forDepartment('arbitrary-folder'); check(false,'unconfigured destination rejected'); }
catch(RuntimeException $e) { check(count($calls)===$count,'reject before network'); }
$wrongDrive=true;
try { $client->forDepartment('IT'); check(false,'wrong drive rejected'); }
catch(RuntimeException $e) { check(true,'wrong drive rejected'); }
echo 'PASS: '.($checks-$start)." destination checks (mock HTTP)\n";
