<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/regression.php';
$start = $checks;
foreach (array_keys(SharePointTables::LABELS) as $type) {
    $empty = SharePointTables::csv($type, []);
    check(str_starts_with($empty, "\xEF\xBB\xBF") && substr_count($empty, "\n") === 1, 'empty export has Thai headers: ' . $type);
}
$csv = SharePointTables::csv('devices', [['svp_device_id' => 1, 'svp_device_name' => '=SUM(1,2)', 'serial_number' => '00123', 'is_active' => 0, 'password' => 'DO_NOT_EXPORT', 'svp_department_name' => 'ฝ่ายไอที']]);
check(str_contains($csv, "'=SUM") && !str_contains($csv, 'DO_NOT_EXPORT'), 'formula neutralized and unlisted fields excluded');
check(str_contains($csv, '00123') && str_contains($csv, 'ฝ่ายไอที'), 'serial bytes and Thai preserved');
try { SharePointTables::csv('../users', []); check(false, 'unknown type rejected'); } catch (InvalidArgumentException $e) { check(true, 'unknown type rejected'); }
$config = ['tenant_id'=>'test.example','client_id'=>'app','client_secret'=>'secret','site_id'=>'site','drive_id'=>'drive','export_folder_id'=>'folder'];
$calls = [];
$client = new SharePointClient($config, static function ($method, $url, $headers, $body) use (&$calls) {
    $calls[] = compact('method','url','headers','body');
    return ['status'=>200,'body'=>json_encode($method === 'POST' ? ['access_token'=>'token','expires_in'=>3600] : ['id'=>'file'])];
});
foreach (array_keys(SharePointTables::LABELS) as $type) {
    $client->exportTable($type, []);
    $call = end($calls);
    check($call['method'] === 'PUT' && str_contains($call['url'], '/items/folder:/' . $type . '-') && str_ends_with($call['url'], '.csv:/content'), 'correct remote file: ' . $type);
}
$routes = require BASE_PATH . '/app/Config/routes.php';
check($routes['POST /sharepoint/export-tables'][2] === ['auth','role:admin'], 'exports restricted to admin POST');
Database::$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db = Database::$db;
$db->exec('CREATE TABLE device (svp_device_id INTEGER, svp_device_name TEXT, serial_number TEXT)');
$db->exec('CREATE TABLE users (svp_user_id INTEGER, first_name TEXT, last_name TEXT)');
$db->exec('CREATE TABLE pm_schedule (pm_schedule_id INTEGER, svp_device_id INTEGER, responsible_user_id INTEGER, is_active INTEGER)');
$db->exec('INSERT INTO pm_schedule VALUES (1,99,NULL,0), (2,100,NULL,1)');
$rows = SharePointTables::rows('pm_schedules');
check(count($rows) === 2 && $rows[0]['is_active'] === 0, 'all plans including inactive and missing relations retained');
echo 'PASS: ' . ($checks - $start) . " SharePoint table checks (mock HTTP / SQLite)\n";
