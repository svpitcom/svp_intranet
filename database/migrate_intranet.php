<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Config/config.php';
require dirname(__DIR__) . '/app/Config/database.php';
$db = Database::connect();
foreach (explode(';', file_get_contents(__DIR__ . '/intranet_modules.sql')) as $sql) {
    if (trim($sql) !== '') $db->exec($sql);
}
echo "Intranet module tables are ready. Existing data was retained.\n";
$db->exec(file_get_contents(__DIR__.'/machine_repair_forms.sql'));
