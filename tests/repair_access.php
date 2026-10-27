<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
ob_start();
require __DIR__.'/intranet.php';
require_once BASE_PATH.'/app/Middleware/AuthMiddleware.php';
$start=$checks;
$fixtureDate=(new DateTimeImmutable('yesterday',new DateTimeZone('Asia/Bangkok')))->format('Y-m-d');
$machineRequestInput=['machine_name'=>'TEST-MACHINE','department_name'=>'Production','report_date'=>$fixtureDate,'report_time'=>'08:00','request_priority'=>'P2'];
$machineRepairInput=['receiver_name'=>'Repairer','received_date'=>$fixtureDate,'repair_priority'=>'P2','repair_method'=>'internal','abnormality'=>'ME','repairer_name'=>'Repairer','repair_date'=>$fixtureDate,'repair_end_date'=>$fixtureDate,'start_time'=>'09:00','end_time'=>'10:00','cause'=>'Wear','repair_detail'=>'Replaced part','prevention'=>'Inspect regularly','technicians'=>['Repairer'],'parts'=>[]];
class RepairAccessHarness extends WorkController {
    protected function view(string $view,array $data=[],string $layout='main'): void { extract($data); require BASE_PATH.'/app/Views/'.$view.'.php'; }
    protected function redirect(string $path): void { throw new RedirectSignal($path); }
}
$routes=require BASE_PATH.'/app/Config/routes.php';
foreach($routes as &$handler) if($handler[0]==='WorkController') $handler[0]='RepairAccessHarness';
unset($handler);
$router=new Router($routes);
$newIds=[];
foreach ([null,1,2,6,13,15] as $offset=>$dept) {
    $uid=20+$offset;
    $stmt=$db->prepare('INSERT INTO users(svp_user_id,username,first_name,last_name,user_role,is_active,svp_department_id) VALUES(?,?,?,?,?,?,?)');
    $stmt->execute([$uid,'repair-'.$uid,'Repair','User','user',1,$dept]);
    $u=(new User())->findById($uid); $_SESSION['user']=$u; $_GET=$_POST=$_FILES=[];
    http_response_code(200); ob_start(); $router->dispatch('GET','/tickets/create'); $formHtml=ob_get_clean();
    check(http_response_code()===200 && str_contains($formHtml,'ส่งแจ้งซ่อม'),'active staff can open repair form, department '.($dept??'unassigned'));
    check(str_contains(render('partials/sidebar',[]),APP_URL.'/tickets'),'every department has repair menu');
    $_POST=['_csrf'=>Session::csrfToken(),'title'=>'แจ้งซ่อมทดสอบ '.$uid,'description'=>'รายละเอียดปัญหาทดสอบ','category'=>'facility','priority'=>'normal','device_id'=>'','location'=>'พื้นที่ทดสอบ','requester_id'=>'4'];
    try { $router->dispatch('POST','/tickets/create'); check(false,'create redirects'); }
    catch(RedirectSignal $e) { check(str_starts_with($e->getMessage(),'/work/'),'create redirects to accessible ticket'); }
    // Find the ticket by its authenticated requester, not a notification insert ID.
    $newId=(int)$db->query('SELECT MAX(id) FROM work_items WHERE requester_id='.$uid)->fetchColumn();
    $newIds[$uid]=$newId;
    $saved=$work->detail($newId,$u);
    check((int)$saved['requester_id']===$uid && $saved['status']==='open','requester cannot be spoofed');
    check($work->listing($u,'ticket')['total']===1,'ordinary staff only list own ticket');
    ob_start(); $router->dispatch('GET','/work/'.$newId); $detailHtml=ob_get_clean();
    check(str_contains($detailHtml,'แจ้งซ่อมทดสอบ '.$uid),'created ticket can be opened');
    $work->act($newId,$u,'comment',1,'รายละเอียดเพิ่มเติม',null);
    check(count($work->history($newId))===2,'requester can add information');
    denied(fn()=>$work->act($newId,$u,'assign',2,'',3),'ordinary requester cannot assign');
}
$dcc=(new User())->findById(24); $_SESSION['user']=$dcc;
$dccId=$newIds[24];
$work->act($dccId,$manager,'assign',2,'',3);
$work->act($dccId,$other,'start',3,'',null);
$work->act($dccId,$other,'resolve',4,'แก้ไขแล้ว',null);
$work->act($dccId,$dcc,'close',5,'ใช้งานได้แล้ว',null);
check($work->detail($dccId,$dcc)['status']==='closed','DCC requester can confirm repaired ticket');
denied(fn()=>$work->detail($newIds[20],$dcc),'DCC cannot read unrelated ticket');
denied(fn()=>$work->act($newIds[20],$dcc,'comment',2,'test',null),'DCC cannot change unrelated ticket');
$dccManager=array_replace($dcc,['user_role'=>'manager']);
check(!WorkItem::managesTickets($dccManager),'DCC access does not grant global repair management');
denied(fn()=>$work->detail($newIds[20],$dccManager),'DCC manager cannot inspect unrelated repairs');
denied(fn()=>$work->createItem('request',$request,$dcc),'DCC request creation remains blocked');
$db->exec('UPDATE work_items SET approver_id=24 WHERE id='.$rid);
denied(fn()=>$work->detail($rid,$dccManager),'shared detail route cannot expose request');
denied(fn()=>$work->act($rid,$dccManager,'approve',2,'test',null),'shared action route cannot approve request');
check($work->listing($dccManager,'request')['total']===0,'DCC request list empty');
http_response_code(200); ob_start(); $router->dispatch('GET','/work/'.$rid.'/attachment'); ob_end_clean();
check(http_response_code()===404,'request attachment protected on shared route');
$_POST=['_csrf'=>'invalid']; http_response_code(200); ob_start(); $router->dispatch('POST','/tickets/create'); ob_end_clean();
check(http_response_code()===403,'repair creation keeps CSRF protection');
$disabled=array_replace($dcc,['is_active'=>0]);
denied(fn()=>$work->createItem('ticket',$data,$disabled),'inactive account cannot create');
check(!WorkItem::canRead($work->detail($dccId,$dcc),$disabled),'inactive account cannot read');
foreach(['GET /tickets','GET /tickets/create','POST /tickets/create','GET /work/{id}','POST /work/{id}/action','GET /work/{id}/attachment'] as $route) check(in_array('auth',$routes[$route][2],true),'repair route still requires login');
http_response_code(200);
$maintenance=(new User())->findById(23);
$machineTicket=$work->createItem('ticket',array_replace($data,$machineRequestInput,['title'=>'Machine queue test','category'=>'machine']),$owner);
check(WorkItem::canRead($work->detail($machineTicket,$owner),$maintenance),'Maintenance reads unassigned machine ticket');
check(WorkItem::receivesMachineTickets(array_replace($maintenance,['svp_department_id'=>'6'])),'string department ID supported');
check((int)$work->detail($machineTicket,$maintenance)['id']===$machineTicket,'Maintenance opens machine details directly');
check($work->listing($maintenance,'ticket','Machine queue test','open')['total']===1,'Maintenance filtered list includes new machine ticket');
check($work->overview($maintenance)['ticket']>=2,'Maintenance overview includes machine queue and own work');
denied(fn()=>$work->detail($machineTicket,$dcc),'DCC cannot read unrelated machine queue');
denied(fn()=>$work->detail($machineTicket,$other),'ordinary IT user cannot read machine queue');
denied(fn()=>$work->detail($newIds[20],$maintenance),'Maintenance cannot read unrelated facility ticket');
denied(fn()=>$work->detail($rid,$maintenance),'machine visibility does not expose approval requests');
denied(fn()=>$work->detail($machineTicket,array_replace($maintenance,['is_active'=>0])),'inactive Maintenance cannot read machine queue');
check(!in_array('assign',WorkItem::actions($work->detail($machineTicket,$maintenance),$maintenance),true),'queue visibility does not grant assignment authority');
denied(fn()=>$work->act($machineTicket,$maintenance,'start',1,'',null),'unassigned reader cannot start another ticket');
$work->act($machineTicket,$manager,'assign',1,'',23);
check($work->listing($maintenance,'ticket','Machine queue test')['total']===1,'machine queue remains visible after assignment');
$_SESSION['user']=$maintenance; $_GET=['q'=>'Machine queue test']; $_POST=[];
http_response_code(200); ob_start(); $router->dispatch('GET','/tickets'); $queueHtml=ob_get_clean();
check(http_response_code()===200 && str_contains($queueHtml,'Machine queue test') && str_contains($queueHtml,'เครื่องจักรทั้งหมด'),'Maintenance page renders team queue');
ob_start(); $router->dispatch('GET','/work/'.$machineTicket); $queueDetail=ob_get_clean();
check(http_response_code()===200 && str_contains($queueDetail,'Machine queue test'),'Maintenance detail route matches listing access');
echo 'PASS: '.($checks-$start)." repair access checks (isolated fixtures; no live tickets or messages).\n";
ob_end_flush();
