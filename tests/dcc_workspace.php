<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ob_start();
require __DIR__.'/regression.php';
$start=$checks;
$dcc=['svp_department_id'=>13,'user_role'=>'user','is_active'=>1,'first_name'=>'<DCC>'];
$allowed=['GET /','GET /login','POST /login','POST /logout','GET /users','GET /users/search','GET /document-control','GET /document-control/files','GET /document-control/create','POST /document-control/create','GET /document-control/{id}/edit','POST /document-control/{id}/edit'];
$allowed=array_merge($allowed,['GET /kpi','GET /kpi/create','POST /kpi/create','POST /kpi/{id}/result','GET /kpi/{id}/history','GET /kpi/forms','GET /kpi/forms/create','POST /kpi/forms/create','GET /kpi/forms/{id}','POST /kpi/forms/{id}','GET /kpi/forms/{id}/export']);
$allowed=array_merge($allowed,['GET /tickets','GET /tickets/create','POST /tickets/create','GET /work/{id}','POST /work/{id}/action','GET /work/{id}/attachment']);
$allowed=array_merge($allowed,['GET /notifications','POST /notifications/read']);
$allowed[]='GET /sharepoint';
foreach (require BASE_PATH.'/app/Config/routes.php' as $route=>$handler) {
    check(DccWorkspace::allows($dcc,$route)===in_array($route,$allowed,true),'DCC route '.$route);
    check(DccWorkspace::allows(array_replace($dcc,['user_role'=>'admin']),$route),'admin unchanged '.$route);
}
check(DccWorkspace::restricted(array_replace($dcc,['svp_department_id'=>'13','user_role'=>'manager'])),'DCC manager restricted');
check(!DccWorkspace::restricted(array_replace($dcc,['svp_department_id'=>2])),'other departments unchanged');
check(!DccWorkspace::allows($dcc,'GET /future-module'),'new routes denied by default');
$_SESSION['user']=$dcc;
$html=render('partials/sidebar',[]);
foreach(['/devices','/requests','/announcements','/departments','/positions','/pm-schedules'] as $path) check(!str_contains($html,APP_URL.$path),'hidden sidebar '.$path);
check(str_contains($html,APP_URL.'/notifications'),'DCC has own repair notification menu');
check(str_contains($html,APP_URL.'/tickets'),'DCC has repair menu');
check(str_contains($html,APP_URL.'/users') && str_contains($html,APP_URL.'/document-control'),'DCC sidebar allowed links');
check(str_contains($html,APP_URL.'/kpi'),'DCC sidebar includes own department KPI');
check(!DccWorkspace::allows($dcc,'POST /kpi/forms/{id}/sharepoint'),'DCC cannot send KPI to SharePoint');
check(str_contains($html,'SharePoint แผนกของฉัน') && str_contains($html,APP_URL.'/sharepoint'),'DCC SharePoint menu targets scoped browser');
$_SERVER['REQUEST_URI']=APP_URL.'/document-control/files';
$html=render('partials/sidebar',[]);
check(substr_count($html,'aria-current="page"')===1,'only SharePoint DCC is active on file browser');
check(DccWorkspace::allows($dcc,'GET /sharepoint') && !DccWorkspace::allows($dcc,'POST /sharepoint/export'),'department browser allowed but exports remain forbidden');
$_SERVER['REQUEST_URI']=APP_URL.'/document-control?category=IT';
$_GET['category']='IT';
$menuHtml=render('partials/sidebar',[]);
check(!str_contains($menuHtml,'หมวดทะเบียนเอกสาร'),'separate category heading removed');
$previousXmlErrors=libxml_use_internal_errors(true);
$dom=new DOMDocument(); $dom->loadHTML('<?xml encoding="utf-8" ?>'.$menuHtml);
libxml_clear_errors(); libxml_use_internal_errors($previousXmlErrors);
$xp=new DOMXPath($dom);
check($xp->query('//details[contains(@class,"dcc-nav-group")]/summary[contains(.,"Document Control")]')->length===1,'Document Control is expandable parent');
check($xp->query('//details[contains(@class,"dcc-nav-group")]//a[@aria-current="page" and contains(@href,"category=IT")]')->length===1,'selected category nested inside Document Control');
unset($_GET['category']);
$html=render('dashboard/dcc',['user'=>$dcc,'total'=>10,'counts'=>['draft'=>8,'active'=>2,'obsolete'=>0]]);
check(str_contains($html,'&lt;DCC&gt;'),'DCC welcome escaped');
check(str_contains($html,APP_URL.'/tickets/create'),'DCC homepage has repair shortcut');
check(!str_contains($html,'/pm-schedules') && !str_contains($html,'/devices'),'DCC homepage has no PM or device links');
class DccForbiddenController { public function index():void { throw new RuntimeException('Forbidden controller executed'); } }
http_response_code(200);
ob_start(); (new Router(['GET /devices'=>['DccForbiddenController','index']]))->dispatch('GET','/devices'); ob_end_clean();
check(http_response_code()===403,'router denies direct access before controller');
http_response_code(200);
echo 'PASS: '.($checks-$start)." DCC workspace checks\n";
ob_end_flush();
