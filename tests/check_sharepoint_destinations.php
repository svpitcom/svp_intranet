<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Config/config.php';
require BASE_PATH . '/app/Core/SharePointClient.php';
$config = require BASE_PATH . '/app/Config/sharepoint.php';
$client = new SharePointClient($config);
$failed=false;
foreach (array_keys($config['department_folders']) as $code) {
    try { $client->forDepartment($code); echo $code . ": folder verified (read-only)\n"; }
    catch (RuntimeException $e) { $failed=true; echo $code . ': ' . $e->getMessage() . "\n"; }
}
exit($failed ? 1 : 0);
