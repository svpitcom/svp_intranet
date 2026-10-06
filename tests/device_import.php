<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/regression.php';
$start = $checks;
$row = ['svp_device_id'=>'1','svp_device_name'=>'Test laptop','brand_name'=>'Test','model_name'=>'M1','serial_number'=>'00123','device_type_name'=>'Laptop','svp_department_name'=>'IT','first_name'=>'Test','last_name'=>'Owner','is_active'=>'1'];
$bytes = DeviceWorkbook::build([$row]);
$config = ['tenant_id'=>'test.example','client_id'=>'app','client_secret'=>'secret','site_id'=>'site','drive_id'=>'drive','export_folder_id'=>'folder'];
$metadataCalls = 0; $changed = false;
$client = new SharePointClient($config, static function ($method,$url,$headers,$body) use ($bytes,&$metadataCalls,&$changed) {
    if ($method === 'POST') return ['status'=>200,'body'=>'{"access_token":"token","expires_in":3600}'];
    check($method === 'GET', 'download never writes remote files');
    if ($url === 'https://download.example/test') {
        check($headers === [], 'bearer token not forwarded to download URL');
        return ['status'=>200,'body'=>$bytes];
    }
    if (str_ends_with($url, '/content')) return ['status'=>302,'body'=>'','headers'=>['location'=>'https://download.example/test']];
    $metadataCalls++;
    return ['status'=>200,'body'=>json_encode(['id'=>'demo','name'=>SharePointClient::DEVICE_DEMO_NAME,'eTag'=>($changed && $metadataCalls % 2 === 0)?'v2':'v1','parentReference'=>['id'=>'folder']])];
});
$download = $client->downloadDeviceDemo();
check($download['bytes'] === $bytes && $download['eTag'] === 'v1', 'download returns workbook and version');
$rows = DeviceWorkbook::parse($download['bytes']);
check($rows[0]['serial_number'] === '00123', 'round-trip preserves serial leading zeroes');
check($rows[0]['_row'] === 7, 'parser retains worksheet row for validation messages');
$changed = true;
try { $client->downloadDeviceDemo(); check(false, 'changed workbook rejected'); }
catch (RuntimeException $e) { check(str_contains($e->getMessage(), 'เปลี่ยนระหว่าง'), 'download detects concurrent edit'); }
$db = new TestPDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE device_type (device_type_id INTEGER, device_type_name TEXT); CREATE TABLE department (svp_department_id INTEGER, svp_department_name TEXT); CREATE TABLE users (svp_user_id INTEGER, first_name TEXT, last_name TEXT); CREATE TABLE device (svp_device_id INTEGER PRIMARY KEY AUTOINCREMENT, svp_device_name TEXT, brand_name TEXT, model_name TEXT, serial_number TEXT, device_type_id INTEGER, svp_department_id INTEGER, svp_user_id INTEGER, is_active INTEGER)');
$db->exec("INSERT INTO device_type VALUES (1,'Laptop'); INSERT INTO department VALUES (1,'IT'); INSERT INTO users VALUES (1,'Test','Owner'); INSERT INTO device (svp_device_id,svp_device_name) VALUES (1,'Old')");
$new = $row; $new['svp_device_id']=''; $new['serial_number']='NEW-001';
$result = DeviceWorkbook::import([$row,$new], $db);
check($result === ['updated'=>1,'created'=>1], 'existing device updates and blank ID inserts');
check($db->query('SELECT serial_number FROM device WHERE svp_device_id=1')->fetchColumn() === '00123', 'values saved to isolated database');
$invalid = $row; $invalid['svp_device_id']='999'; $invalid['_row']=15;
try { DeviceWorkbook::import([$new,$invalid],$db); check(false,'missing ID rejected'); }
catch (RuntimeException $e) {
    check((int)$db->query('SELECT COUNT(*) FROM device')->fetchColumn() === 2,'failed import rolls back all inserts');
    check(str_contains($e->getMessage(),'แถว 15') && str_contains($e->getMessage(),'คอลัมน์ A'),'missing ID gives actual row and corrective action');
}
$invalid = $row; $invalid['device_type_name']='Unknown';
try { DeviceWorkbook::import([$invalid],$db); check(false,'unknown type rejected'); }
catch (RuntimeException $e) { check(str_contains($e->getMessage(),'ประเภทอุปกรณ์'),'reference validation explains missing type'); }
check((require BASE_PATH . '/app/Config/routes.php')['POST /devices/import-excel'][2] === ['auth','role:admin'],'import requires admin');
echo 'PASS: ' . ($checks-$start) . " device import checks (isolated database, no remote writes)\n";
