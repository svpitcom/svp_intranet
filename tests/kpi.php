<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/regression.php';
$start=$checks;
Database::$db=new TestPDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db=Database::$db;
$db->exec('CREATE TABLE department(svp_department_id INTEGER PRIMARY KEY,svp_department_name TEXT)');
$db->exec('CREATE TABLE users(svp_user_id INTEGER PRIMARY KEY,first_name TEXT,last_name TEXT,svp_department_id INTEGER,is_active INTEGER)');
$db->exec("INSERT INTO department VALUES(2,'IT'),(6,'Maintenance'),(13,'DCC')");
$db->exec("INSERT INTO users VALUES(1,'Admin','A',2,1),(2,'Worker','B',2,1),(3,'Worker','C',6,1),(4,'Peer','D',2,1)");
$db->exec('CREATE TABLE kpi_definitions(id INTEGER PRIMARY KEY AUTOINCREMENT,period TEXT,title TEXT,department_id INTEGER,user_id INTEGER,source TEXT,direction TEXT,target REAL,weight REAL,unit TEXT,created_by INTEGER,created_at TEXT)');
$db->exec('CREATE TABLE kpi_results(kpi_id INTEGER PRIMARY KEY,actual REAL,note TEXT,version INTEGER,updated_by INTEGER,updated_at TEXT)');
$db->exec('CREATE TABLE kpi_history(id INTEGER PRIMARY KEY AUTOINCREMENT,kpi_id INTEGER,actual REAL,note TEXT,version INTEGER,actor_id INTEGER,created_at TEXT)');
function rejectsKpi(callable $fn,string $label): void { try { $fn(); } catch (RuntimeException $e) { check(true,$label); return; } check(false,$label); }
$admin=['svp_user_id'=>1,'user_role'=>'admin','svp_department_id'=>2,'is_active'=>1];
$manager=array_replace($admin,['user_role'=>'manager']);
$worker=array_replace($admin,['svp_user_id'=>2,'user_role'=>'user']);
$other=array_replace($worker,['svp_user_id'=>3,'svp_department_id'=>6]);
check(!KpiPolicy::allows(null),'guest denied');
check(!KpiPolicy::allows(array_replace($admin,['is_active'=>0])),'inactive denied');
check(KpiPolicy::allows(array_replace($worker,['svp_department_id'=>13])),'DCC can access department KPI');
check(KpiPolicy::manages($admin,6) && !KpiPolicy::manages($manager,6),'manager department scope');
check(KpiPolicy::score(5,10,'higher')===50.0,'higher score');
check(KpiPolicy::score(20,10,'higher')===100.0,'score capped');
check(KpiPolicy::score(20,10,'lower')===50.0,'lower score');
check(KpiPolicy::score(0,10,'lower')===100.0,'zero actual lower');
check(KpiPolicy::score(null,10,'higher')===null,'missing not zero');
foreach (['2026-00','2026-13','2026-1','bad'] as $p) rejectsKpi(fn()=>KpiPolicy::period($p),'invalid period');
foreach (['-1','NaN','INF','1e5','1.23456','',[]] as $v) rejectsKpi(fn()=>KpiPolicy::number($v),'invalid actual');
$model=new Kpi(); $period=substr(Portal::today(),0,7);
$data=['period'=>$period,'title'=>'<script>test</script>','department_id'=>'2','user_id'=>'','source'=>'manual','direction'=>'higher','target'=>'10','weight'=>'1','unit'=>'รายการ'];
$id=$model->createDefinition($worker,$data);
check(empty($model->find($id)['user_id']),'worker creates department KPI');
rejectsKpi(fn()=>$model->createDefinition($worker,array_replace($data,['department_id'=>'6'])),'worker cannot create cross department');
rejectsKpi(fn()=>$model->createDefinition($manager,array_replace($data,['department_id'=>'6'])),'cannot create cross department');
rejectsKpi(fn()=>$model->createDefinition($admin,array_replace($data,['user_id'=>'3'])),'person must match department');
rejectsKpi(fn()=>$model->createDefinition($admin,array_replace($data,['target'=>'0'])),'zero target rejected');
rejectsKpi(fn()=>$model->createDefinition($admin,array_replace($data,['source'=>'unknown'])),'unknown source rejected');
rejectsKpi(fn()=>$model->saveResult($id,$other,['actual'=>'5','note'=>'','version'=>'0']),'other department cannot write result');
$model->saveResult($id,$worker,['actual'=>'5','note'=>'<img src=x>','version'=>'0']);
check((int)$db->query('SELECT actor_id FROM kpi_history')->fetchColumn()===2,'department result records actual worker');
rejectsKpi(fn()=>$model->saveResult($id,$manager,['actual'=>'9','note'=>'','version'=>'0']),'stale update rejected');
check((int)$db->query('SELECT COUNT(*) FROM kpi_history')->fetchColumn()===1,'stale update no audit row');
check((float)$db->query('SELECT actual FROM kpi_results')->fetchColumn()===5.0,'stale update preserves result');
$second=$model->createDefinition($manager,array_replace($data,['weight'=>'3']));
$model->saveResult($second,$manager,['actual'=>'10','note'=>'','version'=>'0']);
rejectsKpi(fn()=>$model->createDefinition($worker,array_replace($data,['user_id'=>'2'])),'new personal KPI rejected');
$personal=$model->insert(array_replace($data,['user_id'=>2,'created_by'=>1,'created_at'=>Portal::now()]));
$model->saveResult($personal,$worker,['actual'=>'0','note'=>'','version'=>'0']);
$peer=$model->insert(array_replace($data,['user_id'=>4,'created_by'=>1,'created_at'=>Portal::now()]));
rejectsKpi(fn()=>$model->detail($peer,$worker),'peer KPI hidden');
rejectsKpi(fn()=>$model->history($id,$other),'cross-department history hidden');
$rows=$model->listing($worker,$period);
check(count($rows['rows'])===2,'department page excludes legacy personal KPIs');
$s=$rows['summaries']['2:0']; check(abs($s['weighted']/$s['weight']-87.5)<0.001,'weighted score per scope');
check(!isset($rows['summaries']['2:2']),'personal scores not mixed into department summary');
check((float)$db->query('SELECT actual FROM kpi_results WHERE kpi_id='.$personal)->fetchColumn()===0.0,'legacy result preserved');
$maintenance=$model->createDefinition($other,array_replace($data,['department_id'=>'6','title'=>'Maintenance KPI']));
check(count($model->listing($admin,$period,6)['rows'])===1,'admin filters department');
check(count($model->listing($other,$period)['rows'])===1,'worker scoped to own department');
rejectsKpi(fn()=>$model->listing($worker,$period,6),'forged department filter rejected');
rejectsKpi(fn()=>$model->listing(array_replace($worker,['svp_department_id'=>null]),$period),'unassigned user cannot read department KPI');
$autoId=$model->createDefinition($admin,array_replace($data,['source'=>'repair_hours','direction'=>'higher']));
check($model->find($autoId)['direction']==='lower','automatic direction enforced');
rejectsKpi(fn()=>$model->saveResult($autoId,$admin,['actual'=>'1','note'=>'','version'=>'0']),'automatic result cannot be overridden');
$future=$model->createDefinition($admin,array_replace($data,['period'=>'2099-12']));
rejectsKpi(fn()=>$model->saveResult($future,$admin,['actual'=>'1','note'=>'','version'=>'0']),'future result denied');
class KpiFixture extends Kpi { protected function automatic(string $period,int $dept,?int $person): array { return ['pm_completed'=>5.0,'pm_success_rate'=>null,'tickets_resolved'=>2.0,'repair_hours'=>null]; } }
$fixture=new KpiFixture();
$page=$fixture->listing($admin,$period);
check($page['summaries']['2:0']['filled']<$page['summaries']['2:0']['total'],'missing automatic average makes provisional score');
$_SESSION['user']=$admin;
$html=render('kpi/index',$page+['period'=>$period,'user'=>$admin]);
check(str_contains($html,'&lt;script&gt;test&lt;/script&gt;')&&!str_contains($html,'<script>test</script>'),'title escaped');
check(str_contains($html,'&lt;img src=x&gt;'),'notes escaped');
check(str_contains($html,'name="_csrf"'),'result form has CSRF');
$form=render('kpi/form',['departments'=>$model->departments($manager),'people'=>$model->people($manager),'data'=>[]]);
check(str_contains($form,'name="period"')&&str_contains($form,'name="source"'),'definition form renders');
check(!str_contains($form,'name="user_id"'),'definition form has no personal assessment selector');
$_SESSION['user']=$worker;
$workerPage=$fixture->listing($worker,$period);
$workerHtml=render('kpi/index',$workerPage+['period'=>$period,'user'=>$worker]);
check(str_contains($workerHtml,'กำหนด KPI แผนก') && str_contains($workerHtml,'บันทึกผลงาน'),'worker has create and number-entry controls');
check(!str_contains($workerHtml,'Maintenance KPI') && !str_contains($workerHtml,'<select class="form-select" id="kpi-department"'),'worker cannot select or see another department');
$routes=require BASE_PATH.'/app/Config/routes.php';
foreach (['GET /kpi/create','POST /kpi/create','GET /kpi/forms/create','POST /kpi/forms/create','POST /kpi/forms/{id}'] as $route) check($routes[$route][2]===['auth'],'department write route reaches scoped controller');
$hist=render('kpi/history',['row'=>$model->detail($id,$admin),'history'=>$model->history($id,$admin)]);
check(str_contains($hist,'&lt;img src=x&gt;'),'history escaped');
check(count($model->people($manager))===3,'people dropdown scoped');
$_SESSION['user']=$worker; check(str_contains(render('partials/sidebar',[]),APP_URL.'/kpi'),'KPI menu visible');
$dccWorker=array_replace($worker,['svp_department_id'=>13]);
$_SESSION['user']=$dccWorker; check(str_contains(render('partials/sidebar',[]),APP_URL.'/kpi'),'DCC KPI menu visible');
$dccId=$model->createDefinition($dccWorker,array_replace($data,['department_id'=>'13','title'=>'DCC department KPI']));
$model->saveResult($dccId,$dccWorker,['actual'=>'8','note'=>'DCC result','version'=>'0']);
check(count($model->listing($dccWorker,$period)['rows'])===1,'DCC reads own KPI');
rejectsKpi(fn()=>$model->detail($id,$dccWorker),'DCC cannot access IT KPI');
rejectsKpi(fn()=>$model->saveResult($id,$dccWorker,['actual'=>'8','note'=>'','version'=>'1']),'DCC cannot write IT result');
rejectsKpi(fn()=>$model->listing($dccWorker,$period,2),'DCC cannot forge IT filter');
class KpiDepartmentViewHarness extends KpiController {
    protected function view(string $view,array $data=[],string $layout='main'): void { extract($data); require BASE_PATH.'/app/Views/'.$view.'.php'; }
}
$_GET=[]; http_response_code(200); ob_start(); (new KpiDepartmentViewHarness())->index(); $dccHtml=ob_get_clean(); check(http_response_code()===200 && str_contains($dccHtml,'DCC department KPI'),'DCC controller renders scoped KPI');
http_response_code(200);
if (isset($argv[1]) && is_dir($argv[1])) {
    $css=file_get_contents(BASE_PATH.'/public/assets/vendor/bootstrap/css/bootstrap.min.css').file_get_contents(BASE_PATH.'/public/assets/css/style.css');
    foreach (['kpi-departments-admin'=>$html,'kpi-departments-worker'=>$workerHtml] as $file=>$body) {
        $body=str_replace(['&lt;script&gt;test&lt;/script&gt;','&lt;img src=x&gt;'],['ติดตามงานประจำเดือน (ตัวอย่าง)','ผลการตรวจประจำเดือน (ข้อมูลทดสอบ)'],$body);
        file_put_contents($argv[1].'/'.$file.'.html','<!doctype html><html lang="th"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>KPI department test data</title><style>'.$css.'</style><body><main style="padding:24px"><p class="text-muted small">ข้อมูลทดสอบ · ไม่มีการบันทึกลงฐานจริง</p>'.$body.'</main></body></html>');
    }
}
echo 'PASS: '.($checks-$start)." KPI checks (isolated SQLite; automatic SQL checked separately).\n";
