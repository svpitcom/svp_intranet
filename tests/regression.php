<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Isolated tests: no production config, credentials, database, or uploads are used.
define('BASE_PATH', dirname(__DIR__));
define('APP_URL', '/svp_intranet/public');
define('UPLOAD_PM_RECORD_PATH', __DIR__ . '/missing-uploads');
define('UPLOAD_PM_SCHEDULE_PATH', __DIR__ . '/missing-uploads');
spl_autoload_register(function ($class) {
    foreach (['Core', 'Models', 'Controllers'] as $directory) {
        $file = BASE_PATH . '/app/' . $directory . '/' . $class . '.php';
        if (is_file($file)) { require $file; return; }
    }
});
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
class TestPDO extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return parent::prepare(str_replace(' FOR UPDATE', '', $query), $options);
    }
}
class Database
{
    public static PDO $db;
    public static function connect(): PDO { return self::$db; }
}
class RedirectSignal extends RuntimeException {}
class RecordHarness extends PmRecordController
{
    protected function redirect(string $path): void { throw new RedirectSignal($path); }
}
class InputHarness extends Controller
{
    public function read(string $key, $default = null) { return $this->input($key, $default); }
}
class RouteHarness
{
    public function edit(int $id): void { echo 'record:' . $id; }
}
$checks = 0;
function check(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException('FAIL: ' . $label);
    $checks++;
}
function render(string $view, array $data): string {
    extract($data);
    ob_start();
    require BASE_PATH . '/app/Views/' . $view . '.php';
    return ob_get_clean();
}
function saveRecord(string $date, string $status = 'completed', string $id = '1'): string {
    $_POST = ['performed_date' => $date, 'result_status' => $status];
    try { (new RecordHarness())->store($id); }
    catch (RedirectSignal $e) { return $e->getMessage(); }
    throw new RuntimeException('Expected redirect');
}
$_SESSION = ['user' => ['svp_user_id' => 1, 'user_role' => 'admin']];
$_GET = $_POST = $_FILES = [];
check(Validation::date('2024-02-29'), 'leap date accepted');
check(!Validation::date('2025-02-29') && !Validation::date('2026-09-31'), 'invalid dates rejected');
check(!Validation::date('tomorrow'), 'relative dates rejected');
check(!Session::validCsrf(null), 'missing CSRF rejected');
check(Session::validCsrf(Session::csrfToken()), 'valid CSRF accepted');
check(!Session::validCsrf(['bad']) && !Session::validCsrf('bad'), 'malformed CSRF rejected');
$_POST = ['password' => ' secret ', 'sort' => ['bad']];
check((new InputHarness())->read('password') === ' secret ', 'password whitespace preserved');
check((new InputHarness())->read('sort', 'id') === 'id', 'array input rejected');
$_POST = [];
check(FileUploader::uploadPdf('document', UPLOAD_PM_RECORD_PATH) === null, 'optional upload');
foreach ([['error' => ['bad']], ['error' => UPLOAD_ERR_PARTIAL], ['error' => UPLOAD_ERR_OK, 'name' => 'x.pdf', 'tmp_name' => __FILE__]] as $file) {
    $_FILES['document'] = $file;
    try { FileUploader::uploadPdf('document', UPLOAD_PM_RECORD_PATH); check(false, 'upload rejected'); }
    catch (RuntimeException $e) { check(true, 'invalid upload rejected'); }
}
$_FILES = [];
check(FileUploader::resolve(BASE_PATH, '../.env') === null, 'path traversal rejected');
$router = new Router(['GET /items/{id}' => ['RouteHarness', 'edit'], 'POST /items/{id}' => ['RouteHarness', 'edit']]);
foreach (['abc', '0', '1abc'] as $id) {
    ob_start(); $router->dispatch('GET', '/items/' . $id); ob_end_clean();
    check(http_response_code() === 404, 'invalid route id rejected');
}
ob_start(); $router->dispatch('GET', '/items/12'); $html = ob_get_clean();
check($html === 'record:12', 'valid route dispatched');
ob_start(); $router->dispatch('POST', '/items/12'); ob_end_clean();
check(http_response_code() === 403, 'POST without CSRF blocked');
$_POST['_csrf'] = Session::csrfToken();
ob_start(); $router->dispatch('POST', '/items/12'); $html = ob_get_clean();
check($html === 'record:12', 'POST with CSRF dispatched');

Database::$db = new TestPDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db = Database::$db;
$db->exec('CREATE TABLE pm_schedule (pm_schedule_id INTEGER PRIMARY KEY, frequency_days INTEGER, last_pm_date TEXT, next_pm_date TEXT)');
$db->exec('CREATE TABLE pm_record (pm_record_id INTEGER PRIMARY KEY, pm_schedule_id INTEGER, performed_by INTEGER, performed_date TEXT, result_status TEXT, notes TEXT, attachment_original_name TEXT, attachment_path TEXT)');
$db->exec("INSERT INTO pm_schedule VALUES (1, 30, NULL, '2026-09-01')");
check(saveRecord('2026-09-30') === '/pm-schedules', 'record saved');
check((new PmSchedule())->find(1)['next_pm_date'] === '2026-10-30', 'next date updated');
saveRecord('2026-08-01');
check((new PmSchedule())->find(1)['last_pm_date'] === '2026-09-30', 'backdated record does not rewind schedule');
$count = (int) $db->query('SELECT COUNT(*) FROM pm_record')->fetchColumn();
saveRecord('2026-02-30'); saveRecord('2026-09-30', 'invalid'); saveRecord('2026-09-30', 'completed', '99');
check((int) $db->query('SELECT COUNT(*) FROM pm_record')->fetchColumn() === $count, 'invalid records not inserted');
$db->exec("CREATE TRIGGER fail_update BEFORE UPDATE ON pm_schedule BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
check(saveRecord('2026-10-01') === '/pm-schedules/1/record', 'database failure handled');
check((int) $db->query('SELECT COUNT(*) FROM pm_record')->fetchColumn() === $count && !$db->inTransaction(), 'record insert rolled back on schedule failure');

$schedule = ['pm_schedule_id' => 1, 'pm_title' => '<script>alert(1)</script>', 'svp_device_name' => 'Device', 'checklist' => 'Check'];
$history = [['pm_record_id' => 1, 'performed_date' => '2026-09-30', 'result_status' => 'completed', 'first_name' => 'Test', 'last_name' => 'User', 'notes' => '', 'attachment_path' => str_repeat('a', 32) . '.pdf', 'attachment_original_name' => 'report.pdf']];
$html = render('pm_records/create', compact('schedule', 'history'));
check(substr_count($html, 'list-group-item') === 1, 'history displayed once');
check(str_contains($html, 'multipart/form-data') && str_contains($html, '/pm-records/1/attachment'), 'upload form and authenticated link');
check(!str_contains($html, '<script>alert(1)</script>'), 'record title escaped');
check(str_contains(render('pm_records/index', ['records' => []]), 'colspan="6"'), 'empty history page renders');
foreach (require BASE_PATH . '/app/Config/routes.php' as $route) check(method_exists($route[0], $route[1]), 'route handler exists');
echo "PASS: {$checks} regression checks (isolated SQLite; MySQL locking not simulated).\n";
