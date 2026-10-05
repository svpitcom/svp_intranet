<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Config/config.php';
require BASE_PATH . '/app/Config/database.php';
foreach (['Model','DeviceWorkbook','SharePointClient'] as $class) require BASE_PATH . '/app/Core/' . $class . '.php';
require BASE_PATH . '/app/Models/Device.php';
try {
    if (($argv[1] ?? '') === '--prepare-template') {
        // Remove original sample rows from the application template.
        $bytes = DeviceWorkbook::build([]);
        file_put_contents(BASE_PATH . '/app/Templates/devices-demo.xlsx', $bytes);
        echo "Empty template prepared\n";
        exit;
    }
    if (($argv[1] ?? '') !== '--upload-demo') throw new RuntimeException('Specify --upload-demo');
    $rows = (new Device())->allWithDevice();
    $result = (new SharePointClient())->syncDeviceDemo($rows);
    echo json_encode(['rows'=>count($rows),'name'=>$result['name']??null,'webUrl'=>$result['webUrl']??null],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) { echo 'Demo sync failed: ' . ($e instanceof PDOException ? 'Database unavailable' : $e->getMessage()) . PHP_EOL; exit(1); }
