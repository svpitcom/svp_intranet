<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/regression.php';
$start = $checks;
function inspectWorkbook(array $rows): array {
    $temp = tempnam(sys_get_temp_dir(), 'svp-test-'); $path = $temp . '.zip';
    try {
        file_put_contents($path, DeviceWorkbook::build($rows));
        $zip = new PharData($path);
        $xml = $zip['xl/worksheets/sheet1.xml']->getContent();
        $doc = new DOMDocument(); check($doc->loadXML($xml, LIBXML_NONET), 'valid worksheet XML');
        $xp = new DOMXPath($doc); $xp->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        check($xp->query('//x:sheetData/x:row[number(@r)>=7]')->length === count($rows), 'no stale rows after refresh');
        check($xp->query('//x:f')->length === 0, 'source values never become formulas');
        foreach (new RecursiveIteratorIterator($zip) as $entry) {
            if (str_contains(str_replace('\\','/',$entry->getPathname()), '/xl/tables/')) {
                $table = new DOMDocument(); $table->loadXML($entry->getContent());
                check($table->documentElement->getAttribute('ref') === 'A6:J' . max(7,count($rows)+6), 'table extends to new data size');
            }
        }
        check(isset($zip['xl/worksheets/sheet2.xml']), 'mapping sheet preserved');
        return [$xml, $xp->evaluate('string(//x:c[@r="E7"]/x:is/x:t)')];
    } finally { unset($zip,$entry); unlink($path); unlink($temp); }
}
$rows = [['svp_device_id'=>1,'svp_device_name'=>'=SUM(1,2) & <test>','serial_number'=>'00123','is_active'=>0]];
[$xml,$serial] = inspectWorkbook($rows);
check($serial === '00123', 'serial remains literal text with leading zeroes');
check(str_contains($xml, '&amp;') && str_contains($xml, '&lt;test&gt;'), 'XML escaping');
inspectWorkbook([]);
$ordered = DeviceWorkbook::parse(DeviceWorkbook::build([
    ['svp_device_id'=>'10','svp_device_name'=>'A','serial_number'=>'TEN'],
    ['svp_device_id'=>'2','svp_device_name'=>'Z','serial_number'=>'TWO'],
    ['svp_device_id'=>'1','svp_device_name'=>'M','serial_number'=>'ONE'],
]));
check(array_column($ordered,'svp_device_id') === ['1','2','10'], 'Excel rows sorted numerically by device ID, not name or text ID');
check(array_column($ordered,'serial_number') === ['ONE','TWO','TEN'], 'sorting preserves complete device rows');
inspectWorkbook(array_fill(0, 70, $rows[0]));
$config = ['tenant_id'=>'test.example','client_id'=>'app','client_secret'=>'secret','site_id'=>'site','drive_id'=>'drive','export_folder_id'=>'folder'];
$writes = []; $status = 200; $wrongName = false;
$client = new SharePointClient($config, static function ($method,$url,$headers,$body) use (&$writes,&$status,&$wrongName) {
    if ($method === 'POST') return ['status'=>200,'body'=>'{"access_token":"token","expires_in":3600}'];
    if ($method === 'GET') return ['status'=>200,'body'=>json_encode(['id'=>'demo','name'=>$wrongName?'Master.xlsx':SharePointClient::DEVICE_DEMO_NAME,'eTag'=>'"version1"','parentReference'=>['id'=>'folder']])];
    $writes[] = compact('url','headers','body');
    return ['status'=>$status,'body'=>'{"id":"demo"}'];
});
$client->syncDeviceDemo($rows);
check(str_ends_with($writes[0]['url'], '/items/demo/content'), 'updates exact existing demo item');
check(in_array('If-Match: "version1"',$writes[0]['headers'],true), 'conditional update uses remote version');
foreach ([412,423] as $error) {
    $status = $error;
    try { $client->syncDeviceDemo($rows); check(false,'error must fail'); }
    catch (RuntimeException $e) { check(str_contains($e->getMessage(),(string)$error),'conflict/lock reported'); }
}
$wrongName = true; $before=count($writes);
try { $client->syncDeviceDemo($rows); check(false,'wrong file rejected'); }
catch (RuntimeException $e) { check(count($writes)===$before,'non-demo workbook not overwritten'); }
check((require BASE_PATH . '/app/Config/routes.php')['POST /devices/sync-excel'][2]===['auth','role:admin'],'admin-only POST');
echo 'PASS: ' . ($checks-$start) . " device workbook checks (no remote writes)\n";
