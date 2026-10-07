<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/regression.php';
$start = $checks;
$db = new TestPDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
Database::$db = $db;
$db->exec("CREATE TABLE department (svp_department_id INTEGER, svp_code_department TEXT);
INSERT INTO department VALUES (1, 'DCC'), (2, 'IT'), (3, ' dcc '), (4, 'DCC-OTHER')");
$dcc = ['user_role' => 'user', 'is_active' => 1, 'svp_department_id' => 13];
check(DocumentControlAccess::allows($dcc), 'active DCC user allowed');
check(DocumentControlAccess::allows(array_replace($dcc, ['user_role' => 'manager'])), 'DCC manager allowed');
check(DocumentControlAccess::allows(array_replace($dcc, ['user_role' => 'admin', 'svp_department_id' => 2])), 'admin outside DCC allowed');
foreach ([1, 2, 3, 4, 99, 0, null, '13invalid'] as $id) check(!DocumentControlAccess::allows(array_replace($dcc, ['svp_department_id' => $id])), 'other or missing department denied even with DCC code');
check(DocumentControlAccess::allows(array_replace($dcc, ['svp_department_id' => '13'])), 'string department ID from database allowed');
check(!DocumentControlAccess::allows(null), 'anonymous denied');
check(!DocumentControlAccess::allows(array_replace($dcc, ['is_active' => 0])), 'inactive DCC denied');
check(!DocumentControlAccess::allows(['user_role' => 'admin', 'is_active' => 0]), 'inactive admin denied');
$_SESSION['user'] = $dcc;
$_SERVER['REQUEST_URI'] = APP_URL . '/document-control';
$html = render('partials/sidebar', []);
check(str_contains($html, '/document-control') && !str_contains($html, '/sharepoint'), 'DCC sidebar without admin SharePoint tools');
$html = render('dashboard/index', ['user' => $dcc, 'today' => '2026-10-07', 'stats' => ['users' => 0, 'devices' => 0, 'overdue' => 0, 'upcoming' => 0], 'schedules' => []]);
check(str_contains($html, '/document-control') && !str_contains($html, '/sharepoint'), 'DCC dashboard shortcut only');
$dcc['svp_department_id'] = 2;
$_SESSION['user'] = $dcc;
check(!DocumentControlAccess::allows($dcc), 'department change immediately revokes access');
$html = render('partials/sidebar', []);
check(!str_contains($html, '/document-control'), 'other department menu hidden');
$routes = require BASE_PATH . '/app/Config/routes.php';
foreach ($routes as $route => $handler) {
    if (str_contains($route, '/document-control')) check($handler[2] === ['auth', 'documentControl'], 'all document routes enforce department policy after auth');
}
check($routes['GET /sharepoint'][2] === ['auth', 'role:admin'], 'general SharePoint remains admin only');
require BASE_PATH . '/app/Middleware/DocumentControlMiddleware.php';
$_SESSION['user'] = ['user_role' => 'admin', 'is_active' => 1];
(new DocumentControlMiddleware())->handle();
check(true, 'middleware accepts admin');
echo 'PASS: ' . ($checks - $start) . " Document Control access checks\n";
