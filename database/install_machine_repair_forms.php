<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Config/config.php';
require BASE_PATH.'/app/Config/database.php';
Database::connect()->exec(file_get_contents(__DIR__.'/machine_repair_forms.sql'));
echo "MN-F-001-02 table ready; existing work retained.\n";
