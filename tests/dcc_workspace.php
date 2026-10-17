<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/regression.php';
$start=$checks;
$dcc=['svp_department_id'=>13,'user_role'=>'user','is_active'=>1,'first_name'=>'<DCC>'];
$allowed=['GET /','GET /login','POST /login','POST /logout','GET /users','GET /users/search','GET /document-control','GET /document-control/files','GET /document-control/create','POST /document-control/create','GET /document-control/{id}/edit','POST /document-control/{id}/edit'];
foreach (require BASE_PATH.'/app/Config/routes.php' as $route=>$handler) {
    check(DccWorkspace::allows($dcc,$route)===in_array($route,$allowed,true),'DCC route '.$route);
    check(DccWorkspace::allows(array_replace($dcc,['user_role'=>'admin']),$route),'admin unchanged '.$route);
}
check(DccWorkspace::restricted(array_replace($dcc,['svp_department_id'=>'13','user_role'=>'manager'])),'DCC manager restricted');
check(!DccWorkspace::restricted(array_replace($dcc,['svp_department_id'=>2])),'other departments unchanged');
check(!DccWorkspace::allows($dcc,'GET /future-module'),'new routes denied by default');
$_SESSION['user']=$dcc;
$html=render('partials/sidebar',[]);
foreach(['/devices','/tickets','/requests','/announcements','/notifications','/departments','/positions','/pm-schedules','/sharepoint'] as $path) check(!str_contains($html,APP_URL.$path),'hidden sidebar '.$path);
check(str_contains($html,APP_URL.'/users') && str_contains($html,APP_URL.'/document-control'),'DCC sidebar allowed links');
check(str_contains($html,'SharePoint DCC') && str_contains($html,APP_URL.'/document-control/files'),'DCC SharePoint menu targets scoped browser');
$_SERVER['REQUEST_URI']=APP_URL.'/document-control/files';
$html=render('partials/sidebar',[]);
check(substr_count($html,'aria-current="page"')===1,'only SharePoint DCC is active on file browser');
check(!DccWorkspace::allows($dcc,'GET /sharepoint') && !DccWorkspace::allows($dcc,'POST /sharepoint/export'),'general library and exports remain forbidden');
$html=render('dashboard/dcc',['user'=>$dcc,'total'=>10,'counts'=>['draft'=>8,'active'=>2,'obsolete'=>0]]);
check(str_contains($html,'&lt;DCC&gt;'),'DCC welcome escaped');
check(!str_contains($html,'/pm-schedules') && !str_contains($html,'/devices'),'DCC homepage has no PM or device links');
class DccForbiddenController { public function index():void { throw new RuntimeException('Forbidden controller executed'); } }
http_response_code(200);
ob_start(); (new Router(['GET /devices'=>['DccForbiddenController','index']]))->dispatch('GET','/devices'); ob_end_clean();
check(http_response_code()===403,'router denies direct access before controller');
http_response_code(200);
echo 'PASS: '.($checks-$start)." DCC workspace checks\n";
