<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ob_start();
require __DIR__.'/repair_access.php';
$start=$checks;
class RepairNotificationHarness extends NotificationController {
    protected function view(string $view,array $data=[],string $layout='main'): void { extract($data); require BASE_PATH.'/app/Views/'.$view.'.php'; }
    protected function redirect(string $path): void { throw new RedirectSignal($path); }
}
$routes['GET /notifications'][0]='RepairNotificationHarness';
$routes['POST /notifications/read'][0]='RepairNotificationHarness';
$router=new Router($routes);
$notifications=new PortalNotification();
$case=$work->createItem('ticket',array_replace($data,['title'=>'Closure notification test']),$dcc);
$work->act($case,$manager,'assign',1,'',3);
$work->act($case,$other,'start',2,'',null);
$before=(int)$db->query('SELECT COUNT(*) FROM portal_notifications WHERE user_id=20')->fetchColumn();
$work->act($case,$other,'resolve',3,'เปลี่ยนอะไหล่ <script>test</script>',null);
check($work->detail($case,$dcc)['status']==='resolved','resolve waits for confirmation');
$notice=$db->query('SELECT * FROM portal_notifications WHERE user_id=24 AND work_id='.$case.' ORDER BY id DESC')->fetch();
check(str_contains($notice['message'],'ซ่อมเสร็จแล้ว') && str_contains($notice['message'],'ยืนยันปิดเคส'),'requester notified with next step');
check($notice['read_at']===null,'completion notification starts unread');
$notifications->markRead(24,true);
check($work->detail($case,$dcc)['status']==='resolved','reading completion notice does not confirm closure');
check((int)$db->query('SELECT COUNT(*) FROM portal_notifications WHERE user_id=20')->fetchColumn()===$before,'unrelated employee not notified');
$_SESSION['user']=$dcc; $_GET=$_POST=[];
ob_start(); $router->dispatch('GET','/work/'.$case); $html=ob_get_clean();
check(str_contains($html,'รับทราบผลซ่อมและยืนยันปิดเคส') && str_contains($html,'ยังพบปัญหา ส่งกลับแก้ไข'),'requester gets clear confirmation controls');
check(str_contains($html,'&lt;script&gt;test&lt;/script&gt;') && !str_contains($html,'<script>test</script>'),'repair result safely escaped');
denied(fn()=>$work->act($case,$dcc,'reopen',4,'',null),'return requires problem detail');
$work->act($case,$dcc,'reopen',4,'ยังใช้งานไม่ได้',null);
check($work->detail($case,$dcc)['status']==='in_progress','requester can return for repair');
$notice=$db->query('SELECT message FROM portal_notifications WHERE user_id=3 AND work_id='.$case.' ORDER BY id DESC')->fetchColumn();
check(str_contains($notice,'ส่งกลับแก้ไข'),'assigned repairer notified of return');
$work->act($case,$other,'resolve',5,'ทดสอบแล้ว ใช้งานได้',null);
$_POST=['_csrf'=>'invalid','version'=>'6','action'=>'close'];
http_response_code(200); ob_start(); $router->dispatch('POST','/work/'.$case.'/action'); ob_end_clean();
check(http_response_code()===403 && $work->detail($case,$dcc)['status']==='resolved','confirmation requires CSRF');
$_POST=['_csrf'=>Session::csrfToken(),'version'=>'6','action'=>'close','note'=>'รับทราบ'];
try { $router->dispatch('POST','/work/'.$case.'/action'); check(false,'confirmation redirects'); } catch(RedirectSignal $e) { check($e->getMessage()==='/work/'.$case,'confirmation returns to case'); }
check($work->detail($case,$dcc)['status']==='closed','requester confirmation closes case');
$history=$work->history($case); $last=end($history);
check($last['action']==='close' && (int)$last['actor_id']===24,'acknowledgment records actual requester');
check(str_contains($db->query('SELECT message FROM portal_notifications WHERE user_id=3 AND work_id='.$case.' ORDER BY id DESC')->fetchColumn(),'ปิดเคสแล้ว'),'repairer receives closure notification');
denied(fn()=>$work->act($case,$dcc,'close',6,'',null),'repeated stale confirmation rejected');
// Management closure still informs the requester and records who actually closed it.
$managed=$work->createItem('ticket',$data,$dcc);
$work->act($managed,$manager,'start',1,'',null);
$work->act($managed,$manager,'resolve',2,'ซ่อมเสร็จ',null);
$work->act($managed,$manager,'close',3,'ปิดโดยผู้จัดการ',null);
check(str_contains($db->query('SELECT message FROM portal_notifications WHERE user_id=24 AND work_id='.$managed.' ORDER BY id DESC')->fetchColumn(),'ปิดเคสแล้ว'),'management closure informs requester');
// A restricted user's historic request/unrelated repair notices must stay inaccessible.
$insert=$db->prepare('INSERT INTO portal_notifications(user_id,work_id,message,created_at) VALUES(24,?,?,?)');
$insert->execute([$rid,'HIDDEN_REQUEST_NOTICE',Portal::now()]);
$insert->execute([$newIds[20],'HIDDEN_UNRELATED_NOTICE',Portal::now()]);
$visible=$notifications->listing(24,1,true);
check($visible['total']===$notifications->listing(24)['total']-2,'DCC pagination excludes forbidden notices');
check($notifications->unread(24,true)===$notifications->unread(24)-2,'DCC unread count uses same scope');
$_GET=$_POST=[]; http_response_code(200);
ob_start(); $router->dispatch('GET','/notifications'); $html=ob_get_clean();
check(http_response_code()===200 && !str_contains($html,'HIDDEN_') && str_contains($html,'ปิดเคสแล้ว'),'DCC notification route shows only permitted repairs');
$nav=render('partials/navbar',['navGroups'=>[],'currentPath'=>'/tickets']);
check(str_contains($nav,'/notifications') && str_contains($nav,'ยังไม่อ่าน '.$notifications->unread(24,true).' รายการ'),'topbar exposes scoped unread count');
$_POST=['_csrf'=>'invalid']; http_response_code(200);
ob_start(); $router->dispatch('POST','/notifications/read'); ob_end_clean();
check(http_response_code()===403 && $notifications->unread(24,true)>0,'mark-read requires CSRF');
$otherUnread=$notifications->unread(3);
$_POST=['_csrf'=>Session::csrfToken(),'user_id'=>'3'];
try { $router->dispatch('POST','/notifications/read'); check(false,'mark read redirects'); } catch(RedirectSignal $e) { check($e->getMessage()==='/notifications','mark read returns to notifications'); }
check($notifications->unread(24,true)===0 && $notifications->unread(24)===2,'mark read affects only allowed DCC notices');
check($notifications->unread(3)===$otherUnread,'posted user ID cannot mark another user notifications');
check($work->detail($managed,$dcc)['version']===4,'reading notifications does not change case');
echo 'PASS: '.($checks-$start)." repair notification checks (isolated fixtures).\n";
ob_end_flush();
