<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
ob_start(); require __DIR__.'/repair_access.php'; $start=$checks;
$db->exec("INSERT INTO users(svp_user_id,username,first_name,last_name,user_role,is_active,svp_department_id) VALUES(26,'inactive-maint','Inactive','Maintenance','user',0,6)");
$case=$work->createItem('ticket',array_replace($data,$machineRequestInput,['category'=>'machine']),$dcc);
$item=$work->detail($case,$manager);
check(array_map('intval',array_column($work->assigneesFor($item),'svp_user_id'))===[23],'machine choices include active Maintenance only');
foreach ([3,4,24,26,999] as $invalid) {
    denied(fn()=>$work->act($case,$admin,'assign',1,'',$invalid),'reject out-of-department, inactive or missing assignee '.$invalid);
}
check((int)$work->detail($case,$manager)['version']===1 && count($work->history($case))===1,'rejected assignments do not change work or audit');
$_SESSION['user']=$manager; $_GET=$_POST=[];
ob_start(); $router->dispatch('GET','/work/'.$case); $html=ob_get_clean();
$previous=libxml_use_internal_errors(true); $dom=new DOMDocument(); $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html); libxml_clear_errors(); libxml_use_internal_errors($previous);
$xpath=new DOMXPath($dom); $ids=[];
foreach ($xpath->query('//select[@name="assignee_id"]/option[@value!=""]') as $option) $ids[]=(int)$option->getAttribute('value');
check($ids===[23],'actual rendered assignment dropdown filtered');
check(str_contains($html,'เลือกได้เฉพาะพนักงานแผนก Maintenance'),'restriction explained in UI');
$_POST=['_csrf'=>Session::csrfToken(),'action'=>'assign','version'=>'1','assignee_id'=>'3'];
try { $router->dispatch('POST','/work/'.$case.'/action'); } catch(RedirectSignal $e) {}
check($work->detail($case,$manager)['assignee_id']===null,'forged POST cannot bypass dropdown');
$work->act($case,$admin,'assign',1,'',23);
check((int)$work->detail($case,$manager)['assignee_id']===23,'admin can assign active Maintenance');
check(WorkItem::canAssignTo($item,array_replace($maintenance,['svp_department_id'=>'6'])),'string department IDs supported');
$facility=$work->detail($newIds[20],$manager);
check(in_array(3,array_map('intval',array_column($work->assigneesFor($facility),'svp_user_id')),true),'non-machine assignment options unchanged');
$db->exec('UPDATE users SET is_active=0 WHERE svp_user_id=23');
check($work->assigneesFor($item)===[],'empty department handled without falling back to other departments');
echo 'PASS: '.($checks-$start)." repair assignee checks (isolated fixtures).\n";
ob_end_flush();
