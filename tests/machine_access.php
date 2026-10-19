<?php
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/regression.php';
$start=$checks;
foreach ([['user',6,true],['manager','6',true],['admin',2,true],['user',2,false],['manager',3,false],['user',13,false],['user',null,false]] as [$role,$dept,$expected]) {
    $u=['user_role'=>$role,'svp_department_id'=>$dept,'is_active'=>1];
    check(MachineAccess::allows($u)===$expected,'machine policy role/department');
    $_SESSION['user']=$u;
    $html=render('partials/sidebar',[]);
    check(str_contains($html,APP_URL.'/machines')===$expected,'machine sidebar role/department');
}
check(!MachineAccess::allows(null),'guest denied');
check(!MachineAccess::allows(['user_role'=>'admin','is_active'=>0]),'inactive admin denied');
$_SESSION['user']=['user_role'=>'user','svp_department_id'=>2,'is_active'=>1];
http_response_code(200); ob_start(); (new DeviceController())->machines(); ob_end_clean();
check(http_response_code()===403,'direct machines request denied before querying devices');
$_GET['group']='machine';
http_response_code(200); ob_start(); (new DeviceController())->index(); ob_end_clean();
check(http_response_code()===403,'machine group query also denied');
unset($_GET['group']); http_response_code(200);
echo 'PASS: '.($checks-$start)." machine access checks\n";
