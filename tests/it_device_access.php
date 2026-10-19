<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/regression.php';
$start = $checks;
foreach ([['user',2,true],['manager','2',true],['admin',6,true],['user',6,false],['manager',3,false],['user',13,false],['user',null,false]] as [$role,$dept,$expected]) {
    $u = ['user_role'=>$role,'svp_department_id'=>$dept,'is_active'=>1];
    check(ItDeviceAccess::allows($u) === $expected, 'IT policy role/department');
    $_SESSION['user'] = $u;
    check(str_contains(render('partials/sidebar', []), APP_URL.'/it-devices') === $expected, 'IT sidebar role/department');
}
check(!ItDeviceAccess::allows(null), 'guest denied');
check(!ItDeviceAccess::allows(['user_role'=>'admin','is_active'=>0]), 'inactive admin denied');
$_SESSION['user'] = ['user_role'=>'user','svp_department_id'=>6,'is_active'=>1];
http_response_code(200); ob_start(); (new DeviceController())->itDevices(); ob_end_clean();
check(http_response_code() === 403, 'Maintenance direct IT request denied');
$_GET['group'] = 'it';
http_response_code(200); ob_start(); (new DeviceController())->index(); ob_end_clean();
check(http_response_code() === 403, 'IT group query denied');
unset($_GET['group']); http_response_code(200);
echo 'PASS: '.($checks-$start)." IT access checks\n";
