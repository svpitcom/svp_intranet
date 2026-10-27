<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ob_start();
require __DIR__.'/repair_access.php';
$start=$checks;
$case=$work->createItem('ticket',array_replace($data,$machineRequestInput,['category'=>'machine']),$dcc);
$row=$work->detail($case,$maintenance);
check(in_array('claim',WorkItem::actions($row,$maintenance),true),'Maintenance can claim unassigned machine repair');
check(!in_array('claim',WorkItem::actions($row,$dcc),true),'requester cannot claim machine work');
denied(fn()=>$work->act($case,$dcc,'claim',1,'',null),'server denies requester claim');
denied(fn()=>$work->act($newIds[23],$maintenance,'claim',2,'',null),'cannot claim own unrelated category');
denied(fn()=>$work->act($machineTicket,$maintenance,'claim',2,'',null),'cannot take already assigned machine work');
denied(fn()=>$work->act($case,array_replace($maintenance,['is_active'=>0]),'claim',1,'',null),'inactive staff cannot claim');
$_SESSION['user']=$maintenance; $_GET=$_POST=[];
http_response_code(200); ob_start(); $router->dispatch('GET','/work/'.$case); $html=ob_get_clean();
check(str_contains($html,'name="action" value="claim"') && str_contains($html,'รับงานและเริ่มซ่อม'),'claim is visible submit button');
check(!str_contains($html,'<select id="action"'),'ticket workflow not hidden in dropdown');
check(str_contains($html,'เพิ่มข้อความเท่านั้น'),'comments clearly separate from state changes');
$_POST=['_csrf'=>'invalid','action'=>'claim','version'=>'1'];
ob_start(); $router->dispatch('POST','/work/'.$case.'/action'); ob_end_clean();
check(http_response_code()===403 && $work->detail($case,$maintenance)['status']==='open','claim requires CSRF');
$_POST=['_csrf'=>Session::csrfToken(),'action'=>'claim','version'=>'1','assignee_id'=>'3'];
try { $router->dispatch('POST','/work/'.$case.'/action'); check(false,'claim redirects'); } catch(RedirectSignal $e) { check($e->getMessage()==='/work/'.$case,'claim returns to work'); }
$row=$work->detail($case,$maintenance);
check((int)$row['assignee_id']===23 && $row['status']==='in_progress' && (int)$row['version']===2,'claim assigns authenticated technician and starts atomically');
$events=$work->history($case); $last=end($events);
check($last['action']==='claim' && (int)$last['actor_id']===23,'claim audited');
denied(fn()=>$work->act($case,$maintenance,'claim',1,'',null),'stale claim rejected');
denied(fn()=>$work->act($case,$maintenance,'claim',2,'',null),'second claim with fresh version also denied');
$_POST=[]; ob_start(); $router->dispatch('GET','/work/'.$case); $html=ob_get_clean();
check(str_contains($html,'name="action" value="resolve"') && str_contains($html,'ซ่อมเสร็จ / แจ้งผู้แจ้งตรวจรับ'),'assigned technician has explicit resolve button');
denied(fn()=>$work->act($case,$maintenance,'resolve',2,'',null),'completion requires repair result');
$work->act($case,$maintenance,'comment',2,'ซ่อมเสร็จ',null);
check($work->detail($case,$maintenance)['status']==='in_progress','comment cannot accidentally change status');
$_POST=$machineRepairInput+['_csrf'=>Session::csrfToken(),'action'=>'resolve','version'=>'3','note'=>'ซ่อมและทดสอบเรียบร้อย'];
try { $router->dispatch('POST','/work/'.$case.'/action'); } catch(RedirectSignal $e) {}
check($work->detail($case,$dcc)['status']==='resolved','resolve button moves case to awaiting confirmation');
check(str_contains($db->query('SELECT message FROM portal_notifications WHERE user_id=24 AND work_id='.$case.' ORDER BY id DESC')->fetchColumn(),'ยืนยันปิดเคส'),'completion notifies requester');
$_SESSION['user']=$dcc; $_POST=[];
ob_start(); $router->dispatch('GET','/work/'.$case); $html=ob_get_clean();
check(str_contains($html,'name="action" value="close"') && str_contains($html,'name="action" value="reopen"'),'DCC requester now sees close and return buttons');
ob_start(); $router->dispatch('GET','/work/'.$newIds[24]); $html=ob_get_clean();
check(!str_contains($html,'value="claim"') && !str_contains($html,'value="resolve"'),'requester does not receive technician actions');
echo 'PASS: '.($checks-$start)." repair workflow button checks (isolated fixtures).\n";
ob_end_flush();
