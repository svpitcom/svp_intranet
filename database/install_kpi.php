<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Config/config.php';
require BASE_PATH.'/app/Config/database.php';
$db = Database::connect();
foreach (explode(';', file_get_contents(__DIR__.'/kpi.sql')) as $sql) if (trim($sql) !== '') $db->exec($sql);
echo "KPI tables ready (additive migration; no existing records changed).\n";
