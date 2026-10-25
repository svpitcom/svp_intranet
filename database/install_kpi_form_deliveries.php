<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Config/config.php';
require BASE_PATH.'/app/Config/database.php';
Database::connect()->exec(file_get_contents(__DIR__.'/kpi_form_deliveries.sql'));
echo "KPI SharePoint delivery table ready.\n";
