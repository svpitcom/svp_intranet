<?php
// Explicit, read-only local MySQL compatibility check. Never inserts fixture data.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/Config/config.php';
require BASE_PATH . '/app/Config/database.php';
spl_autoload_register(function ($class) {
    foreach (['Core', 'Models'] as $folder) {
        $file = BASE_PATH . '/app/' . $folder . '/' . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});
$db = Database::connect();
$user = $db->query("SELECT * FROM users WHERE is_active=1 ORDER BY svp_user_id LIMIT 1")->fetch();
if (!$user) throw new RuntimeException('No active user available for read-only check');
$work = new WorkItem();
$work->listing($user, 'ticket');
$work->listing($user, 'request');
$work->overview($user);
$work->people(true);
(new Announcement())->listing($user);
(new PortalNotification())->listing((int)$user['svp_user_id']);
(new MachineRepairForm())->load(1);
$devices = new Device();
foreach (['it', 'machine', 'unclassified'] as $group) {
    $devices->countAll('', $group);
    $devices->paginate(1, 10, 'svp_device_id', 'asc', '', $group);
}
echo "PASS: new portal queries execute on local MySQL (read-only)\n";
