<?php
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/regression.php';
$start=$checks;
// Separate tables in one database. Identical numeric IDs must not mix assets or histories.
Database::$db=new TestPDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$it=Database::$db;
$it->exec('CREATE TABLE users(svp_user_id INTEGER PRIMARY KEY,first_name TEXT,last_name TEXT,user_role TEXT,svp_department_id INTEGER,is_active INTEGER); CREATE TABLE department(svp_department_id INTEGER PRIMARY KEY,svp_department_name TEXT); CREATE TABLE device(svp_device_id INTEGER PRIMARY KEY,svp_device_name TEXT,asset_group TEXT); CREATE TABLE pm_schedule(id INTEGER PRIMARY KEY); CREATE TABLE pm_record(id INTEGER PRIMARY KEY)');
$it->exec("INSERT INTO users VALUES(1,'Admin','Test','admin',2,1),(2,'Maintenance','Manager','manager',6,1),(3,'Maintenance','User','user',6,1),(4,'IT','User','user',2,1),(5,'Disabled','User','manager',6,0); INSERT INTO department VALUES(6,'Maintenance'); INSERT INTO device VALUES(1,'IT-PC-UNCHANGED','it')");
$db=$it;
check(MachineDatabase::connect()===$it,'machine module uses the shared Intranet connection');
$db->exec('PRAGMA foreign_keys=ON');
$sql=file_get_contents(BASE_PATH.'/database/machines.sql');
$sql=preg_replace('/(?:BIGINT|INT) UNSIGNED AUTO_INCREMENT PRIMARY KEY/','INTEGER PRIMARY KEY AUTOINCREMENT',$sql);
$sql=preg_replace('/,\s*INDEX[^\n]+/','',$sql);
$sql=preg_replace('/ENGINE=InnoDB DEFAULT CHARSET=utf8mb4(?: COLLATE=utf8mb4_unicode_ci)?/','',$sql);
$sql=str_replace('INSERT IGNORE','INSERT OR IGNORE',$sql); $db->exec($sql);
$machines=new Machine($db); $pm=new MachinePm($db);
$admin=(new User())->findById(1); $manager=(new User())->findById(2); $worker=(new User())->findById(3); $other=(new User())->findById(4); $disabled=(new User())->findById(5);
function rejectMachine(callable $fn,string $message): void { try{$fn();}catch(RuntimeException $e){check(true,$message);return;} check(false,$message); }
$input=['code'=>'M-001','name'=>'เครื่องจักรทดสอบ','machine_type'=>'เครื่องฉีด','brand'=>'Test','model'=>'Model 1','serial_number'=>'SN-001','location'=>'สายการผลิต A','department_id'=>'6','owner_id'=>'3','is_active'=>'1'];
foreach([$other,$disabled] as $denied) rejectMachine(fn()=>$machines->save(null,$input,$denied),'unauthorized registry write denied');
rejectMachine(fn()=>$machines->listing($other),'IT account cannot list machines');
$id=$machines->save(null,$input,$worker); check($id===1,'Maintenance user creates machine with independent ID 1');
check((new Device())->findByDeviceId(1)['svp_device_name']==='IT-PC-UNCHANGED','IT asset with same ID unchanged');
rejectMachine(fn()=>(new Device())->insert(['asset_group'=>'machine']),'IT model rejects machine inserts');
rejectMachine(fn()=>(new Device())->update(1,['asset_group'=>'machine']),'IT model rejects machine reclassification');
rejectMachine(fn()=>$machines->save(null,$input,$admin),'duplicate code refused');
rejectMachine(fn()=>$machines->save(null,array_replace($input,['code'=>'M-002','department_id'=>'999']),$admin),'invalid shared department refused');
rejectMachine(fn()=>$machines->save(null,array_replace($input,['code'=>'M-002','owner_id'=>'5']),$admin),'disabled shared owner refused');
$machines->save(1,$input+['version'=>'1'],$worker);
check((int)$machines->detail(1,$worker)['version']===2,'Maintenance user updates registry');
rejectMachine(fn()=>$machines->save(1,$input+['version'=>'1'],$admin),'stale machine update refused');
$plan=['machine_id'=>'1','title'=>'PM เครื่องฉีดรายเดือน','checklist'=>"ตรวจสภาพภายนอก\nตรวจจุดหล่อลื่น",'frequency_days'=>'30','next_pm_date'=>'2026-10-01','responsible_id'=>'3','is_active'=>'1'];
rejectMachine(fn()=>$pm->savePlan(null,$plan,$other),'other department cannot change PM plans');
rejectMachine(fn()=>$pm->savePlan(null,array_replace($plan,['responsible_id'=>'4']),$admin),'PM responsible person must have machine access');
rejectMachine(fn()=>$pm->savePlan(null,array_replace($plan,['machine_id'=>'999']),$admin),'IT-only or nonexistent ID cannot create machine plan');
rejectMachine(fn()=>$pm->savePlan(null,array_replace($plan,['frequency_days'=>'0']),$admin),'zero frequency rejected');
$planId=$pm->savePlan(null,$plan,$worker); check($planId===1,'Maintenance user creates PM plan');
$post=['version'=>'1','performed_date'=>'2026-10-01','result_status'=>'completed','checks'=>[['result'=>'normal','note'=>'ตรวจเรียบร้อย'],['result'=>'normal','note'=>'']],'notes'=>''];
rejectMachine(fn()=>$pm->record(1,$post,$other),'IT user cannot write machine PM');
rejectMachine(fn()=>$pm->record(1,array_replace($post,['performed_date'=>'2999-01-01']),$worker),'future completion refused');
rejectMachine(fn()=>$pm->record(1,array_replace($post,['checks'=>[]]),$worker),'blank checklist cannot be completed');
rejectMachine(fn()=>$pm->record(1,array_replace($post,['checks'=>[['result'=>'issue']]]),$worker),'abnormal cannot be normal overall');
$partial=array_replace($post,['result_status'=>'partial','checks'=>[]]);
$partialId=$pm->record(1,$partial,$worker); check($pm->plan(1,$admin)['next_pm_date']==='2026-10-01','partial retains due date');
rejectMachine(fn()=>$pm->record(1,$partial,$worker),'replay of submitted form refused');
$recordId=$pm->record(1,array_replace($post,['version'=>'2']),$worker);
check($pm->plan(1,$admin)['next_pm_date']==='2026-10-31','normal completion advances machine plan');
rejectMachine(fn()=>$pm->savePlan(1,$plan+['version'=>'1'],$manager),'stale plan cannot rewind completion');
$report=$pm->report($recordId,$worker); check($report['machine_name']===$input['name'] && count($report['data']['checks'])===2,'history stores identity and checklist snapshot');
$machines->save(1,array_replace($input,['version'=>'2','name'=>'ชื่อใหม่']),$admin);
check($pm->report($recordId,$admin)['machine_name']===$input['name'],'renaming registry preserves recorded identity');
$otherId=$machines->save(null,array_replace($input,['code'=>'M-002']),$admin);
rejectMachine(fn()=>$pm->savePlan(1,array_replace($plan,['machine_id'=>(string)$otherId,'version'=>'3']),$admin),'plan machine cannot be changed');
$issue=array_replace($post,['version'=>'3','result_status'=>'issue_found','checks'=>[['result'=>'issue','note'=>'ต้องแก้ไข'],['result'=>'normal']]]);
$pm->record(1,$issue,$worker); check($pm->plan(1,$admin)['next_pm_date']==='2026-10-31','issue retains due date');
$before=(int)$db->query('SELECT COUNT(*) FROM machine_pm_records')->fetchColumn();
$db->exec("CREATE TRIGGER fail_machine_event BEFORE INSERT ON machine_events BEGIN SELECT RAISE(ABORT,'audit failure'); END");
rejectMachine(fn()=>$pm->record(1,array_replace($post,['version'=>'4']),$worker),'audit failure rolls back');
check((int)$db->query('SELECT COUNT(*) FROM machine_pm_records')->fetchColumn()===$before && (int)$pm->plan(1,$admin)['version']===4,'record and schedule rollback together');
$db->exec('DROP TRIGGER fail_machine_event');
$machines->save(1,array_replace($input,['version'=>'3','is_active'=>'0']),$admin);
rejectMachine(fn()=>$pm->record(1,array_replace($post,['version'=>'4']),$worker),'inactive machine cannot receive PM');
$machines->save(1,array_replace($input,['version'=>'4','is_active'=>'1']),$admin);
$pm->savePlan(1,array_replace($plan,['version'=>'4','is_active'=>'0']),$worker);
check((int)$pm->plan(1,$worker)['is_active']===0,'Maintenance user updates PM plan');
rejectMachine(fn()=>$pm->record(1,array_replace($post,['version'=>'5']),$worker),'inactive plan cannot receive PM');
check((int)$it->query('SELECT COUNT(*) FROM device')->fetchColumn()===1 && (int)$it->query('SELECT COUNT(*) FROM pm_schedule')->fetchColumn()===0 && (int)$it->query('SELECT COUNT(*) FROM pm_record')->fetchColumn()===0,'machine registry/plans/records never enter IT tables');
check($pm->listing($worker,'records')['total']===3,'machine history listing');
for($i=3;$i<=17;$i++) $machines->save(null,array_replace($input,['code'=>'M-'.str_pad((string)$i,3,'0',STR_PAD_LEFT)]),$admin);
check(count($machines->listing($worker,'',2)['rows'])===2,'machine pagination uses DB offset');
check($machines->listing($worker,'SN-001')['total']===17,'search by serial');
class MachineViewHarness extends MachineController {
    public Machine $fixtureMachines; public MachinePm $fixturePm;
    protected function machines(): Machine { return $this->fixtureMachines; }
    protected function pm(): MachinePm { return $this->fixturePm; }
    protected function view(string $view,array $data=[],string $layout='main'): void { extract($data); require BASE_PATH.'/app/Views/'.$view.'.php'; }
    protected function redirect(string $path): void { throw new RedirectSignal($path); }
}
$controller=new MachineViewHarness(); $controller->fixtureMachines=$machines; $controller->fixturePm=$pm; $_GET=$_POST=[];
foreach([$other,$disabled] as $denied) { $_SESSION['user']=$denied; foreach(['index','plans','records','edit','save','planEdit','planSave','recordForm','recordSave','report'] as $method) { ob_start(); $controller->$method('1'); ob_end_clean(); check(http_response_code()===403,'controller access '.$method); http_response_code(200); } }
$_SESSION['user']=$worker; ob_start(); $controller->index(); $html=ob_get_clean(); check(str_contains($html,'เพิ่มเครื่องจักร') && !str_contains($html,'IT-PC-UNCHANGED'),'Maintenance user sees registry creation controls');
ob_start(); $controller->planEdit(); $workerPlanForm=ob_get_clean(); check(str_contains($workerPlanForm,'บันทึกแผน'),'Maintenance user can open plan creation form');
$_POST=array_replace($input,['code'=>'','name'=>'<script>alert(1)</script>']); ob_start(); $controller->save(); $invalidHtml=ob_get_clean(); check(http_response_code()===422 && str_contains($invalidHtml,'&lt;script&gt;') && !str_contains($invalidHtml,'<script>alert'),'validation redisplay escapes user data'); http_response_code(200);
$_POST=['_csrf'=>'invalid']; ob_start(); (new Router(['POST /machines/create'=>['MachineController','save',[]]]))->dispatch('POST','/machines/create'); ob_end_clean(); check(http_response_code()===403,'CSRF prevents machine writes'); http_response_code(200); $_POST=[];
foreach(require BASE_PATH.'/app/Config/routes.php' as $route=>$handler) if($handler[0]==='MachineController') check(in_array('auth',$handler[2],true),'machine route auth '.$route);
if (isset($argv[1]) && is_dir($argv[1])) {
    $_SESSION['user']=$manager; ob_start(); $controller->planEdit(); $planHtml=ob_get_clean();
    $css=file_get_contents(BASE_PATH.'/public/assets/vendor/bootstrap/css/bootstrap.min.css').file_get_contents(BASE_PATH.'/public/assets/css/style.css');
    foreach(['machines-preview'=>$html,'machine-plan-preview'=>$planHtml] as $file=>$body) file_put_contents($argv[1].'/'.$file.'.html','<!doctype html><html lang="th"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Machine module fixture preview</title><style>'.$css.'</style><body><main style="padding:24px">'.$body.'</main></body></html>');
}
echo 'PASS machines: '.($checks-$start)." checks using separate tables in one isolated SQLite database; no production machine fixtures.\n";
