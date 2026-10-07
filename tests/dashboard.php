<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/regression.php';
$start = $checks;
$db = new TestPDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
Database::$db = $db;
$db->exec('CREATE TABLE users (svp_user_id INTEGER, first_name TEXT, last_name TEXT, is_active INTEGER);
CREATE TABLE device (svp_device_id INTEGER, svp_device_name TEXT, is_active INTEGER);
CREATE TABLE pm_schedule (pm_schedule_id INTEGER, pm_title TEXT, next_pm_date TEXT, is_active INTEGER, svp_device_id INTEGER, responsible_user_id INTEGER)');
$db->exec("INSERT INTO users VALUES (1, 'Test', 'User', 1), (2, 'Disabled', 'User', 0);
INSERT INTO device VALUES (1, 'Laptop', 1), (2, 'Old', 0);
INSERT INTO pm_schedule VALUES (1, '<script>unsafe</script>', '2026-10-06', 1, 1, 1),
(2, 'Today', '2026-10-07', 1, 1, 1), (3, 'Boundary', '2026-10-13', 1, 1, 1),
(4, 'Future', '2026-10-14', 1, 1, 1), (5, 'Inactive', '2026-10-01', 0, 1, 1),
(6, 'Undated', NULL, 1, 1, NULL)");
$model = new Dashboard();
$data = $model->overview('2026-10-07');
check($data['stats'] === ['users' => 1, 'devices' => 1, 'overdue' => 1, 'upcoming' => 2], 'dashboard count boundaries and active status');
check(array_column($data['schedules'], 'pm_schedule_id') === [1, 2, 3], 'dashboard ordered due tasks only');
$viewData = $data + ['today' => '2026-10-07', 'user' => ['first_name' => '<img>', 'user_role' => 'user', 'is_active' => 1]];
$html = render('dashboard/index', $viewData);
check(str_contains($html, '&lt;script&gt;') && str_contains($html, '&lt;img&gt;') && !str_contains($html, '<script>'), 'dashboard escapes names and task titles');
check(!str_contains($html, '/document-control') && !str_contains($html, '/sharepoint'), 'regular user has no admin shortcuts');
$viewData['user']['user_role'] = 'admin';
$html = render('dashboard/index', $viewData);
check(str_contains($html, '/document-control') && str_contains($html, '/sharepoint'), 'admin has department shortcuts');
for ($id = 10; $id < 20; $id++) $db->exec("INSERT INTO pm_schedule VALUES ($id, 'Extra', '2026-10-07', 1, 1, NULL)");
check(count($model->overview('2026-10-07')['schedules']) === 6, 'dashboard limits task rows');
$db->exec('DELETE FROM pm_schedule');
$viewData = $model->overview('2026-10-07') + ['today' => '2026-10-07', 'user' => []];
check(str_contains(render('dashboard/index', $viewData), 'ไม่มีงาน PM'), 'empty state renders');
$routes = require BASE_PATH . '/app/Config/routes.php';
check($routes['GET /'] === ['DashboardController', 'index', ['auth']], 'home requires authentication and uses dashboard');
echo 'PASS: ' . ($checks - $start) . " dashboard checks\n";
