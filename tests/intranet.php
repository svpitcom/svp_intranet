<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/regression.php';
$start = $checks;
// Exercise the PM safeguards against the existing isolated fixture.
$db->exec('DROP TRIGGER fail_update');
$_FILES = [];
$previous = (new PmSchedule())->find(1);
saveRecord('2026-10-01', 'partial');
check((new PmSchedule())->find(1)['next_pm_date'] === $previous['next_pm_date'], 'partial does not advance PM');
saveRecord('2026-10-01', 'issue_found');
check((new PmSchedule())->find(1)['last_pm_date'] === $previous['last_pm_date'], 'issue found remains pending');
$before = (int)$db->query('SELECT COUNT(*) FROM pm_record')->fetchColumn();
saveRecord((new DateTimeImmutable(Portal::today()))->modify('+1 day')->format('Y-m-d'));
check((int)$db->query('SELECT COUNT(*) FROM pm_record')->fetchColumn() === $before, 'future PM rejected');
$annual = PmSchedule::withAnnualDates([['next_pm_date' => '2026-10-05', 'frequency_days' => 30], ['next_pm_date' => '2025-12-01', 'frequency_days' => 365]], 2026);
check(str_starts_with($annual[0]['plan_next_year'], '2027-'), 'monthly projection is actually in next year');
check($annual[1]['next_pm_date'] === '2025-12-01', 'overdue actual due date retained');
$calendar = PmCalendar::build([['next_pm_date' => '2026-10-05', 'frequency_days' => 7]], '2026-11', new DateTimeImmutable('2026-10-01'));
check($calendar['count'] === 5, 'weekly calendar includes all projected rounds in month');

$db = new TestPDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
Database::$db = $db;
$schema = file_get_contents(BASE_PATH . '/database/intranet_modules.sql');
$schema = str_replace('BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $schema);
$schema = preg_replace('/,\s*INDEX\s+.*?(?=\n\)\s*ENGINE)/s', '', $schema);
$schema = preg_replace('/ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci/', '', $schema);
$db->exec($schema);
$db->exec(str_replace('ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci','',file_get_contents(BASE_PATH.'/database/machine_repair_forms.sql')));
$db->exec('CREATE TABLE users(svp_user_id INTEGER PRIMARY KEY,username TEXT,first_name TEXT,last_name TEXT,email TEXT,user_role TEXT,is_active INTEGER,svp_department_id INTEGER); CREATE TABLE device(svp_device_id INTEGER PRIMARY KEY,svp_device_name TEXT,is_active INTEGER); CREATE TABLE department(svp_department_id INTEGER PRIMARY KEY,svp_department_name TEXT)');
$db->exec("INSERT INTO users VALUES (1,'owner','Owner','One','one@example.test','user',1,1),(2,'manager','Manager','Two','two@example.test','manager',1,1),(3,'other','Other','Three','three@example.test','user',1,2),(4,'admin','Admin','Four','four@example.test','admin',1,1),(5,'disabled','Disabled','Five','five@example.test','manager',0,1); INSERT INTO device VALUES (1,'Device',1); INSERT INTO department VALUES (1,'IT'),(2,'Production')");
$owner = (new User())->findById(1);
$manager = (new User())->findById(2);
$other = (new User())->findById(3);
$admin = (new User())->findById(4);
function denied(callable $callback, string $label): void
{
    $rejected = false;
    try {
        $callback();
    } catch (RuntimeException $e) {
        $rejected = true;
    }
    check($rejected, $label);
}
$work = new WorkItem();
$data = ['title' => '<script>unsafe</script>', 'description' => 'เครื่องมีปัญหา', 'category' => 'it', 'priority' => 'high', 'device_id' => '1', 'location' => 'โรงงาน'];
$id = $work->createItem('ticket', $data, $owner);
check($work->detail($id, $owner)['status'] === 'open', 'ticket starts open');
check(count($work->history($id)) === 1, 'creation audit recorded');
denied(fn() => $work->detail($id, $other), 'unrelated user cannot read ticket');
check($work->listing($other, 'ticket')['total'] === 0, 'listing uses same access boundary');
denied(fn() => $work->act($id, $owner, 'assign', 1, '', 3), 'requester cannot assign');
$work->act($id, $manager, 'assign', 1, 'ส่งช่างตรวจสอบ', 3);
check($work->detail($id, $other)['assignee_id'] == 3, 'assigned user gains access');
denied(fn() => $work->act($id, $other, 'start', 1, '', null), 'stale transition blocked');
$work->act($id, $other, 'start', 2, 'เริ่มตรวจ', null);
denied(fn() => $work->act($id, $other, 'resolve', 3, '', null), 'resolution requires report');
$work->act($id, $other, 'resolve', 3, 'เปลี่ยนสายไฟและทดสอบแล้ว', null);
denied(fn() => $work->act($id, $other, 'close', 4, '', null), 'technician cannot confirm owner closure');
$work->act($id, $owner, 'reopen', 4, 'ยังใช้งานไม่ได้', null);
$work->act($id, $other, 'resolve', 5, 'แก้ไขเพิ่มเติมแล้ว', null);
$work->act($id, $owner, 'close', 6, 'ใช้งานได้แล้ว', null);
check($work->detail($id, $owner)['status'] === 'closed', 'ticket reaches confirmed closed state');
denied(fn() => $work->act($id, $manager, 'start', 7, '', null), 'closed ticket cannot restart');
$work->act($id, $owner, 'comment', 7, 'ขอบคุณ', null);
check(count($work->history($id)) === 8, 'every successful change audited once');
$notification = new PortalNotification();
check($notification->listing(1)['total'] > 0, 'owner receives workflow notifications');
$notification->markRead(1);
check((int)$db->query('SELECT COUNT(*) FROM portal_notifications WHERE user_id=1 AND read_at IS NULL')->fetchColumn() === 0, 'mark read affects owner');
check((int)$db->query('SELECT COUNT(*) FROM portal_notifications WHERE user_id=2 AND read_at IS NULL')->fetchColumn() > 0, 'other notifications unchanged');
$request = $data;
$request['category'] = 'general';
$request['approver_id'] = '2';
$rid = $work->createItem('request', $request, $owner);
check($work->listing($manager, 'request', '', 'pending', 1, true)['total'] === 1, 'approval inbox includes assigned pending request');
check($work->listing($admin, 'request', '', 'pending', 1, true)['total'] === 0, 'approval inbox excludes requests assigned to someone else');
denied(fn() => $work->act($rid, $admin, 'approve', 1, '', null), 'admin cannot bypass designated approver');
denied(fn() => $work->act($rid, $owner, 'approve', 1, '', null), 'requester cannot self approve');
denied(fn() => $work->createItem('request', array_replace($request, ['approver_id' => '1']), $owner), 'self approver rejected');
denied(fn() => $work->createItem('request', array_replace($request, ['approver_id' => '5']), $owner), 'disabled approver rejected');
denied(fn() => $work->detail($rid, $other), 'unrelated user cannot read request');
$work->act($rid, $manager, 'approve', 1, 'อนุมัติตามคำขอ', null);
check($work->detail($rid, $owner)['status'] === 'approved', 'designated approver can approve');
denied(fn() => $work->act($rid, $manager, 'reject', 2, 'เปลี่ยนใจ', null), 'decision cannot be overwritten');
$rid2 = $work->createItem('request', $request, $owner);
denied(fn() => $work->act($rid2, $manager, 'reject', 1, '', null), 'rejection needs reason');
$work->act($rid2, $manager, 'reject', 1, 'ข้อมูลไม่ครบ', null);
$rid3 = $work->createItem('request', $request, $owner);
$work->act($rid3, $owner, 'cancel', 1, 'ไม่ต้องการแล้ว', null);
check($work->detail($rid3, $owner)['status'] === 'cancelled', 'requester can cancel pending request');
$before = (int)$db->query('SELECT COUNT(*) FROM work_items')->fetchColumn();
$db->exec("CREATE TRIGGER fail_notification BEFORE INSERT ON portal_notifications BEGIN SELECT RAISE(ABORT,'test failure'); END");
denied(fn() => $work->createItem('request', $request, $owner), 'notification failure rolls back create');
check((int)$db->query('SELECT COUNT(*) FROM work_items')->fetchColumn() === $before && !$db->inTransaction(), 'no partial work item after failure');
$db->exec('DROP TRIGGER fail_notification');

$announcement = new Announcement();
$news = ['title' => '<img src=x>', 'body' => 'ข้อความสำคัญ', 'status' => 'published', 'department_id' => '1', 'publish_date' => Portal::today(), 'expires_date' => '', 'pinned' => '1'];
denied(fn() => $announcement->saveAnnouncement(null, $news, $owner, 0), 'regular user cannot publish');
$aid = $announcement->saveAnnouncement(null, $news, $manager, 0);
check($announcement->listing($owner)['total'] === 1, 'department audience can see announcement');
check($announcement->listing($other)['total'] === 0, 'other department cannot list announcement');
denied(fn() => $announcement->detail($aid, $other), 'other department direct access denied');
$announcement->acknowledge($aid, $owner, 1);
$announcement->acknowledge($aid, $owner, 1);
check(count($announcement->readers($aid)) === 1, 'acknowledge is idempotent');
$news['body'] = 'แก้ไขเนื้อหา';
$announcement->saveAnnouncement($aid, $news, $manager, 1);
check(count($announcement->readers($aid)) === 0, 'content edit invalidates old acknowledgement');
denied(fn() => $announcement->acknowledge($aid, $owner, 1), 'stale acknowledgement rejected');
denied(fn() => $announcement->saveAnnouncement($aid, $news, $manager, 1), 'stale announcement update rejected');
$announcement->acknowledge($aid, $owner, 2);
$draft = $announcement->saveAnnouncement(null, array_replace($news, ['status' => 'draft']), $manager, 0);
denied(fn() => $announcement->detail($draft, $owner), 'draft direct access denied');
$future = $announcement->saveAnnouncement(null, array_replace($news, ['publish_date' => (new DateTimeImmutable(Portal::today()))->modify('+1 day')->format('Y-m-d')]), $manager, 0);
denied(fn() => $announcement->detail($future, $owner), 'scheduled announcement hidden before date');
$announcement->saveAnnouncement(null, array_replace($news, ['publish_date' => '2020-01-01', 'expires_date' => '2020-01-02']), $manager, 0);
check($announcement->listing($owner)['total'] === 1, 'expired and unpublished excluded');

$_SESSION['user'] = $owner;
$_GET = [];
$item = $work->detail($id, $owner);
$html = render('work/show', ['item' => $item, 'events' => $work->history($id), 'people' => $work->people(), 'actions' => WorkItem::actions($item, $owner)]);
check(!str_contains($html, '<script>unsafe</script>') && str_contains($html, '&lt;script&gt;'), 'work content escaped');
check(str_contains($html, 'name="_csrf"') && str_contains($html, 'name="version"'), 'work form protects CSRF and concurrency');
$html = render('work/index', $work->listing($owner, 'ticket') + ['kind' => 'ticket', 'q' => '', 'status' => '']);
check(str_contains($html, '#' . $id) && !str_contains($html, '<script>unsafe'), 'work listing renders');
$html = render('work/form', ['kind' => 'request', 'data' => [], 'error' => '', 'people' => $work->people(true), 'devices' => []]);
check(str_contains($html, 'name="approver_id"') && str_contains($html, 'multipart/form-data'), 'request form includes approver and upload');
$html = render('announcements/index', $announcement->listing($owner) + ['q' => '', 'manage' => false]);
check(str_contains($html, '&lt;img src=x&gt;') && !str_contains($html, '<img src=x>'), 'announcement content escaped');
$html = render('notifications/index', $notification->listing(1));
check(str_contains($html, '/notifications/read'), 'notification form renders');

class PortalUserHarness extends UserController
{
    protected function redirect(string $path): void
    {
        throw new RedirectSignal($path);
    }
}
$_SESSION['user'] = $admin;
$_POST = ['username' => 'admin', 'first_name' => 'Admin', 'last_name' => 'Four', 'email' => 'four@example.test', 'user_role' => 'user', 'is_active' => '1'];
try {
    (new PortalUserHarness())->update('4');
} catch (RedirectSignal $e) {
}
check((new User())->findById(4)['user_role'] === 'admin', 'acting admin cannot downgrade self');
echo 'PASS: ' . ($checks - $start) . " intranet workflow, permissions, announcements and PM checks (isolated SQLite)\n";
