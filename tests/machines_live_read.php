<?php
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Config/config.php';
require BASE_PATH.'/app/Config/database.php';
spl_autoload_register(function($class){foreach(['Core','Models'] as $folder){$file=BASE_PATH.'/app/'.$folder.'/'.$class.'.php';if(is_file($file)){require $file;return;}}});
$it=Database::connect(); $machines=MachineDatabase::connect();
$it->exec('START TRANSACTION READ ONLY');
try {
    $main=$it->query('SELECT DATABASE()')->fetchColumn(); $separate=$machines->query('SELECT DATABASE()')->fetchColumn();
    if ($main!==$separate || $it!==$machines) throw new RuntimeException('Machine tables must share the Intranet database and connection');
    $user=$it->query("SELECT * FROM users WHERE is_active=1 AND user_role='admin' LIMIT 1")->fetch();
    if (!$user) throw new RuntimeException('No active admin for read-only compatibility check');
    $out=['shared_database'=>$main,'shared_connection'=>true,'it_device_count'=>(int)$it->query('SELECT COUNT(*) FROM device')->fetchColumn(),'it_plan_count'=>(int)$it->query('SELECT COUNT(*) FROM pm_schedule')->fetchColumn(),'it_record_count'=>(int)$it->query('SELECT COUNT(*) FROM pm_record')->fetchColumn()];
    $out['machine_count']=(new Machine())->listing($user)['total'];
    $out['machine_plan_count']=(new MachinePm())->listing($user,'plans')['total'];
    $out['machine_record_count']=(new MachinePm())->listing($user,'records')['total'];
    $out['machine_tables']=array_values(array_intersect($machines->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN),['machines','machine_pm_schedules','machine_pm_records','machine_events','machine_schema']));
    if (count($out['machine_tables'])!==5) throw new RuntimeException('Missing machine tables in main database');
    $check=$it->query("SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='device' AND CONSTRAINT_NAME='chk_device_no_machine'")->fetchColumn();
    if (!$check) throw new RuntimeException('Missing IT isolation constraint');
    $out['it_isolation_constraint']=$check;
    echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
} finally { $it->exec('ROLLBACK'); }
