<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Config/config.php';
require BASE_PATH.'/app/Config/database.php';
require BASE_PATH.'/app/Core/MachineDatabase.php';
$it=Database::connect();
// Never delete legacy assets or cascading PM history as part of installing a schema.
if ((int)$it->query("SELECT COUNT(*) FROM device WHERE asset_group='machine'")->fetchColumn()>0) throw new RuntimeException('พบเครื่องจักรในฐานเดิม ต้องย้ายข้อมูลและตรวจประวัติก่อนติดตั้ง ไม่ได้แก้ข้อมูลเดิม');
$name=$it->query('SELECT DATABASE()')->fetchColumn();
// The earlier separate-schema installation must not contain records that would
// disappear from the UI after switching to tables in the main database.
if ($name!=='svp_machines') {
    $legacy=$it->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='svp_machines'")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['machines','machine_pm_schedules','machine_pm_records','machine_events'] as $table) {
        if (in_array($table,$legacy,true) && (int)$it->query('SELECT COUNT(*) FROM `svp_machines`.`'.$table.'`')->fetchColumn()>0) throw new RuntimeException('ฐาน svp_machines มีข้อมูล ต้องย้ายและตรวจสอบข้อมูลก่อนเปลี่ยนมาใช้ตารางในฐานหลัก');
    }
}
$machine=MachineDatabase::connect();
$tables=$machine->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
if (array_intersect($tables,['machines','machine_pm_schedules','machine_pm_records','machine_events']) && !in_array('machine_schema',$tables,true)) throw new RuntimeException('พบชื่อตารางเครื่องจักรซ้ำโดยไม่มีเวอร์ชันโมดูล หยุดติดตั้งเพื่อป้องกันข้อมูลเดิม');
$machine->exec(file_get_contents(__DIR__.'/machines.sql'));
$constraint=$it->prepare("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='device' AND CONSTRAINT_NAME='chk_device_no_machine'");
$constraint->execute();
if (!(int)$constraint->fetchColumn()) $it->exec("ALTER TABLE device ADD CONSTRAINT chk_device_no_machine CHECK (asset_group <> 'machine')");
echo "Machine tables ready in Intranet database: {$name}. IT records preserved; no fixture data inserted.\n";
