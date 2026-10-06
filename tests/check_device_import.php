<?php
// Explicit live, read-only diagnostic. Imports only into an in-memory SQLite copy.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (($argv[1] ?? '') !== '--read-only') { echo "Usage: php tests/check_device_import.php --read-only\n"; exit(1); }
require dirname(__DIR__) . '/app/Config/config.php';
require BASE_PATH . '/app/Config/database.php';
require BASE_PATH . '/app/Core/SharePointClient.php';
require BASE_PATH . '/app/Core/DeviceWorkbook.php';
class ImportDiagnosticPDO extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return parent::prepare(str_replace(' FOR UPDATE', '', $query), $options);
    }
}
try {
    $download = (new SharePointClient())->downloadDeviceDemo();
    $rows = DeviceWorkbook::parse($download['bytes']);
    echo 'Downloaded and parsed demo: ' . count($rows) . " rows\n";
    $copy = new ImportDiagnosticPDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $copy->exec('CREATE TABLE device_type (device_type_id INTEGER, device_type_name TEXT); CREATE TABLE department (svp_department_id INTEGER, svp_department_name TEXT); CREATE TABLE users (svp_user_id INTEGER, first_name TEXT, last_name TEXT); CREATE TABLE device (svp_device_id INTEGER PRIMARY KEY AUTOINCREMENT, svp_device_name TEXT, brand_name TEXT, model_name TEXT, serial_number TEXT, device_type_id INTEGER, svp_department_id INTEGER, svp_user_id INTEGER, is_active INTEGER)');
    $source = Database::connect();
    $source->exec('START TRANSACTION READ ONLY');
    try {
        foreach (['device_type'=>['device_type_id','device_type_name'], 'department'=>['svp_department_id','svp_department_name'], 'users'=>['svp_user_id','first_name','last_name'], 'device'=>['svp_device_id','svp_device_name','brand_name','model_name','serial_number','device_type_id','svp_department_id','svp_user_id','is_active']] as $table=>$columns) {
            $fields = implode(',', $columns);
            $insert = $copy->prepare('INSERT INTO ' . $table . ' (' . $fields . ') VALUES (' . implode(',',array_fill(0,count($columns),'?')) . ')');
            foreach ($source->query('SELECT ' . $fields . ' FROM ' . $table) as $record) $insert->execute(array_values($record));
        }
    } finally { $source->exec('ROLLBACK'); }
    $result = DeviceWorkbook::import($rows, $copy);
    echo json_encode(['simulation'=>$result,'production_writes'=>0,'remote_writes'=>0],JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $e) {
    echo 'Check failed: ' . ($e instanceof RuntimeException && !$e instanceof PDOException ? $e->getMessage() : get_class($e)) . PHP_EOL;
    exit(1);
}
