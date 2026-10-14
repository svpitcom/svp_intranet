<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Config/config.php';
require dirname(__DIR__).'/app/Config/database.php';
$db=Database::connect();
if (in_array('--inspect',$argv,true)) {
    echo json_encode($db->query('SELECT t.device_type_id,t.device_type_name,COUNT(d.svp_device_id) AS devices FROM device_type t LEFT JOIN device d ON d.device_type_id=t.device_type_id GROUP BY t.device_type_id,t.device_type_name')->fetchAll(),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit;
}
if (!$db->query("SHOW COLUMNS FROM device LIKE 'asset_group'")->fetch()) {
    $db->exec("ALTER TABLE device ADD COLUMN asset_group VARCHAR(20) NOT NULL DEFAULT 'unclassified'");
}
// Only known computer types are classified. Never overwrite a user's chosen group.
$db->exec("UPDATE device d JOIN device_type t ON t.device_type_id=d.device_type_id SET d.asset_group='it' WHERE d.asset_group='unclassified' AND t.device_type_name IN ('Desktop','Laptop','Notebook','Smartphone','Monitor','Speaker','Projector','Printer','Router','Switch','Wi-Fi Access Point','Firewall','UPS','Hub USB')");
echo json_encode($db->query('SELECT asset_group,COUNT(*) AS devices FROM device GROUP BY asset_group')->fetchAll(),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
