<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/regression.php';
$startChecks = $checks;
$_GET = ['search' => ['invalid']];
check(Search::term() === '', 'array search input ignored');
$_GET = ['search' => '  สมชาย  '];
check(Search::term() === 'สมชาย', 'search trimmed');
check(str_contains(Search::suffix(), rawurlencode('สมชาย')), 'query links encode search');
$rows = [
    ['name'=>'ฝ่ายบัญชี', 'code'=>'ACC', 'secret'=>'hidden'],
    ['name'=>'เทคโนโลยีสารสนเทศ', 'code'=>'IT'],
];
check(count(Search::rows($rows, '', ['name','code'])) === 2, 'empty query returns all');
check(count(Search::rows($rows, 'บัญชี acc', ['name','code'])) === 1, 'Thai and case-insensitive multi-field tokens');
check(!Search::rows($rows, 'hidden', ['name','code']), 'only allowed fields searched');
check(!Search::rows($rows, 'ไม่พบ', ['name','code']), 'no results');
check(count(Search::rows([['result_status'=>'issue_found']], 'พบปัญหา', ['result_status'])) === 1, 'Thai record status');
check(count(Search::rows([['pm_status'=>'overdue']], 'เกินกำหนด', ['pm_status'])) === 1, 'Thai schedule status');
check(count(Search::rows([['first_name'=>'สมชาย','last_name'=>'ตัวอย่าง']], 'สมชาย ตัวอย่าง', ['first_name','last_name'])) === 1, 'full name search');

Database::$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db = Database::$db;
$db->exec('CREATE TABLE device_type (device_type_id INTEGER PRIMARY KEY, device_type_name TEXT)');
$db->exec('CREATE TABLE department (svp_department_id INTEGER PRIMARY KEY, svp_department_name TEXT)');
$db->exec('CREATE TABLE users (svp_user_id INTEGER PRIMARY KEY, first_name TEXT, last_name TEXT)');
$db->exec('CREATE TABLE device (svp_device_id INTEGER PRIMARY KEY, svp_device_name TEXT, brand_name TEXT, model_name TEXT, serial_number TEXT, is_active INTEGER, device_type_id INTEGER, svp_department_id INTEGER, svp_user_id INTEGER)');
$db->exec("INSERT INTO device_type VALUES (1,'Notebook'); INSERT INTO department VALUES (1,'ฝ่ายบัญชี'); INSERT INTO users VALUES (1,'สมชาย','ตัวอย่าง')");
$insert = $db->prepare('INSERT INTO device VALUES (?,?,?,?,?,1,1,1,1)');
for ($i=1;$i<=12;$i++) $insert->execute([$i, 'Device ' . $i, 'Lenovo', 'ThinkPad', $i===12?'TARGET_100%':'SERIAL-' . $i]);
$device = new Device();
check($device->countAll() === 12, 'unfiltered device count');
check(count($device->paginate(1,10)) === 10, 'unfiltered first page');
check($device->countAll('TARGET') === 1 && $device->paginate(1,10,'svp_device_id','asc','TARGET')[0]['svp_device_id'] === 12, 'search includes devices beyond first page');
check($device->countAll('ฝ่ายบัญชี สมชาย') === 12, 'joined department and owner search');
check($device->countAll('Notebook thinkpad') === 12, 'type and model search');
foreach (['%', '_', 'TARGET_100%'] as $literal) check($device->countAll($literal) === 1, 'literal wildcard characters');
check($device->countAll("' OR 1=1 --") === 0, 'SQL-like query handled as data');
check(count($device->paginate(2,10,'svp_device_id','desc','Lenovo')) === 2, 'filtered pagination');
check($device->countAll('not-found') === 0 && $device->paginate(1,10,'svp_device_id','asc','not-found') === [], 'empty SQL result');
$_GET = ['search'=>'"><script>alert(1)</script>','sort'=>'name','dir'=>'desc','page'=>'5'];
$html = render('partials/search', ['searchPath'=>'/devices','searchPlaceholder'=>'ค้นหาอุปกรณ์','resultCount'=>0]);
check(!str_contains($html, '<script>'), 'search field and summary escaped');
check(!str_contains($html, 'name="page"') && str_contains($html, 'name="sort"'), 'new search resets page but keeps sort');
echo 'PASS: ' . ($checks-$startChecks) . " search checks (isolated SQLite)\n";
