<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/regression.php';
$start=$checks;
Database::$db=new TestPDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db=Database::$db;
$db->exec('CREATE TABLE users(svp_user_id INTEGER PRIMARY KEY,first_name TEXT,last_name TEXT)');
$db->exec('CREATE TABLE department(svp_department_id INTEGER PRIMARY KEY,svp_department_name TEXT)');
$db->exec('CREATE TABLE device(svp_device_id INTEGER PRIMARY KEY,svp_device_name TEXT,serial_number TEXT,asset_group TEXT,svp_department_id INTEGER)');
$db->exec('CREATE TABLE pm_schedule(pm_schedule_id INTEGER PRIMARY KEY,svp_device_id INTEGER,pm_title TEXT,checklist TEXT,responsible_user_id INTEGER,frequency_days INTEGER,last_pm_date TEXT,next_pm_date TEXT)');
$db->exec('CREATE TABLE pm_record(pm_record_id INTEGER PRIMARY KEY AUTOINCREMENT,pm_schedule_id INTEGER,performed_by INTEGER,performed_date TEXT,result_status TEXT,notes TEXT,attachment_original_name TEXT,attachment_path TEXT)');
$db->exec('CREATE TABLE pm_inspection_form(pm_record_id INTEGER PRIMARY KEY,payload TEXT)');
$db->exec("INSERT INTO users VALUES(1,'เจ้าหน้าที่','ทดสอบ'); INSERT INTO department VALUES(2,'Information Technology'); INSERT INTO device VALUES(1,'SVP-TEST-PC','TEST-005','it',2); INSERT INTO device VALUES(2,'TEST-MACHINE','TEST-M','machine',2); INSERT INTO pm_schedule VALUES(1,1,'PM ทดสอบ','รายการเดิม',1,365,NULL,'2026-10-01'); INSERT INTO pm_schedule VALUES(2,2,'PM Machine','',1,365,NULL,'2026-10-01')");
$schedule=(new PmSchedule())->findWithRelations(1);
$input=PmInspection::defaults($schedule,['first_name'=>'เจ้าหน้าที่','last_name'=>'ทดสอบ']);
$input['performed_date']='2026-10-01'; $input['start_time']='09:00'; $input['end_time']='10:15'; $input['result_status']='completed';
check(count($input['checks'])===39 && count($input['work'])===5 && count($input['safety'])===18,'all original template rows represented');
foreach(PmInspection::MAIN as $r=>$item) check($input['checks'][$r]['result']==='' && $input['checks'][$r]['status']==='','no preselected result');
function rejectInspection(callable $fn,string $label): void { try { $fn(); } catch (RuntimeException $e) { check(true,$label); return; } check(false,$label); }
rejectInspection(fn()=>PmInspection::validate($input,$schedule),'blank checks cannot be complete');
foreach(array_keys(PmInspection::MAIN) as $r) { $input['checks'][$r]['status']='present'; $input['checks'][$r]['result']='normal'; }
$input['checks'][35]['method']='ทดสอบเชื่อมต่อเครือข่าย';
$input['safety'][60]=['topic'=>'ตรวจสายไฟและปลั๊ก','status'=>'ตรวจแล้ว','result'=>'normal','notes'=>'ไม่มีรอยชำรุด'];
$input['notes']='ข้อมูลทดสอบเท่านั้น';
$data=PmInspection::validate($input,$schedule);
rejectInspection(fn()=>PmInspection::validate(array_replace($input,['inspection_device_id'=>'99']),$schedule),'stale device form rejected');
check($data['checks'][35]['method']===$input['checks'][35]['method'],'blank method in original remains editable');
check(PmInspection::minutes('09:00','10:15')===75,'duration calculated');
foreach([['end_time'=>'08:59'],['start_time'=>'24:00'],['start_time'=>'9:00'],['device_name'=>''],['inspector'=>''],['notes'=>str_repeat('x',501)],['checks'=>'bad'],['checks'=>[999=>[]]],['safety'=>[60=>['topic'=>['bad']]]],['checks'=>[13=>['status'=>'invalid']]],['checks'=>[13=>['result'=>'abnormal']]],['work'=>[53=>['topic'=>'แก้ปัญหา','status'=>'pending','result'=>'normal']]]] as $change) {
    $invalid=array_replace_recursive($input,$change);
    rejectInspection(fn()=>PmInspection::validate($invalid,$schedule),'invalid or inconsistent inspection rejected');
}
$absent=$input; $absent['checks'][13]['status']='absent';
rejectInspection(fn()=>PmInspection::validate($absent,$schedule),'absent cannot also report normal');
$absent['checks'][13]['result']=''; check(PmInspection::validate($absent,$schedule)['checks'][13]['result']==='','explicit absent can be skipped');
$partial=PmInspection::defaults($schedule,['first_name'=>'ผู้ทดสอบ']); $partial=array_replace($partial,['start_time'=>'09:00','end_time'=>'10:00','performed_date'=>'2026-10-01','result_status'=>'partial']);
check(PmInspection::validate($partial,$schedule)['checks'][13]['result']==='','partial can retain unchecked items');
$controller=new RecordHarness();
$save=static function(array $post,string $id='1') use($controller): string { $_POST=$post; try { $controller->store($id); } catch(RedirectSignal $e) { return $e->getMessage(); } throw new RuntimeException('Expected redirect'); };
$_SESSION=['user'=>['svp_user_id'=>1,'first_name'=>'เจ้าหน้าที่','last_name'=>'ทดสอบ','user_role'=>'admin','is_active'=>1]];
Session::set('pm_form_old_1',$input);
check($save(array_replace($input,['inspection_device_id'=>'99']))==='/pm-schedules/1/record' && !Session::has('pm_form_old_1'),'stale-device draft cleared so reload displays current device');
check((int)$db->query('SELECT COUNT(*) FROM pm_record')->fetchColumn()===0,'stale-device submission creates no history');
check($save($partial)==='/pm-records/1/form','partial saved and opens report');
check((new PmSchedule())->find(1)['next_pm_date']==='2026-10-01','partial does not advance schedule');
$issue=$input; $issue['checks'][13]['result']='abnormal'; $issue['result_status']='issue_found';
check($save($issue)==='/pm-records/2/form' && (new PmSchedule())->find(1)['last_pm_date']===null,'issue preserves due date');
check($save($input)==='/pm-records/3/form','completed controller saves report');
check((new PmSchedule())->find(1)['next_pm_date']==='2027-10-01','completed advances due date');
$saved=(new PmInspectionForm())->forRecord(3);
check($saved['device_name']==='SVP-TEST-PC' && $saved['revision']===PmInspection::REVISION && $saved['performed_date']==='2026-10-01','complete snapshot persisted');
$db->exec("UPDATE device SET svp_device_name='RENAMED' WHERE svp_device_id=1");
check((new PmInspectionForm())->forRecord(3)===$saved,'device rename cannot rewrite form history');
$count=(int)$db->query('SELECT COUNT(*) FROM pm_record')->fetchColumn();
foreach(['','2026-02-30','2999-01-01'] as $date) $save(array_replace($input,['performed_date'=>$date]));
check((int)$db->query('SELECT COUNT(*) FROM pm_record')->fetchColumn()===$count,'invalid/future/missing dates cannot create reports');
$db->exec("CREATE TRIGGER fail_form BEFORE INSERT ON pm_inspection_form BEGIN SELECT RAISE(ABORT,'simulated form failure'); END");
check($save($input)==='/pm-schedules/1/record','snapshot failure handled');
check((int)$db->query('SELECT COUNT(*) FROM pm_record')->fetchColumn()===$count && !$db->inTransaction(),'PM record and form roll back together');
$db->exec('DROP TRIGGER fail_form');
check($save(['performed_date'=>'2026-10-01','result_status'=>'partial'],'2')==='/pm-schedules','machine flow stays compatible');
check((new PmInspectionForm())->forRecord(4)===null,'legacy/machine PM has no fabricated IT inspection');
$_POST=[]; $_GET=[];
class InspectionViewHarness extends PmRecordController { protected function view(string $view,array $data=[],string $layout='main'): void { extract($data); require BASE_PATH.'/app/Views/'.$view.'.php'; } }
ob_start(); (new InspectionViewHarness())->form('3'); $savedHtml=ob_get_clean();
check(str_contains($savedHtml,'fieldset disabled') && str_contains($savedHtml,'Export Excel') && str_contains($savedHtml,'Export PDF'),'saved report is read-only and exportable');
ob_start(); (new InspectionViewHarness())->form('4'); ob_end_clean(); check(http_response_code()===404,'legacy record cannot pretend to have new form'); http_response_code(200);
$old=array_replace_recursive($input,['device_name'=>'<script>alert(1)</script>']);
$html=render('pm_records/create',['schedule'=>$schedule,'history'=>[],'old'=>$old]);
check(!str_contains($html,'<script>alert(1)</script>') && str_contains($html,'&lt;script&gt;'),'draft safely escaped');
check(substr_count($html,'name="checks[')===39*6 && substr_count($html,'name="work[')===25 && substr_count($html,'name="safety[')===72,'all template input fields render');
check(str_contains($html,'value="09:00"') && str_contains($html,'value="normal" selected'),'draft values retained');
foreach(require BASE_PATH.'/app/Config/routes.php' as $route=>$handler) if(str_contains($route,'/form') && $handler[0]==='PmRecordController') check(in_array('auth',$handler[2],true),'inspection route requires authentication');
$_POST=['_csrf'=>'invalid']; ob_start(); (new Router(['POST /pm-schedules/{id}/record'=>['PmRecordController','store',[]]]))->dispatch('POST','/pm-schedules/1/record'); ob_end_clean(); check(http_response_code()===403,'CSRF blocked'); http_response_code(200); $_POST=[];
$saved['checks'][13]['notes']='=1+1';
$hash=hash_file('sha256',BASE_PATH.'/app/Templates/it-pm-inspection.xlsx');
$bytes=PmInspectionExport::xlsx($saved); $tmp=tempnam(sys_get_temp_dir(),'pm-xlsx-'); $path=$tmp.'.zip'; file_put_contents($path,$bytes); $zip=new PharData($path);
$xml=new DOMDocument(); check($xml->loadXML($zip['xl/worksheets/sheet1.xml']->getContent()),'valid worksheet XML');
$xp=new DOMXPath($xml); $xp->registerNamespace('x','http://schemas.openxmlformats.org/spreadsheetml/2006/main');
$cell=static fn($ref)=>$xp->query('//x:c[@r="'.$ref.'"]')->item(0);
check($cell('C5')->textContent==='SVP-TEST-PC' && $cell('C8')->textContent==='2026-10-01','snapshot header maps to template');
check($cell('G13')->textContent==='✓' && $cell('I13')->textContent==='✓' && $cell('J13')->textContent==='','status and result map independently');
check($cell('B77')!==null && $cell('K77')!==null,'last safety row included');
check($cell('K13')->getAttribute('t')==='inlineStr' && $xp->query('//x:c[@r="K13"]/x:f')->length===0,'formula-like content remains text');
check($cell('G12')->textContent==='' && $cell('I12')->textContent==='','unused rows remain blank');
unset($zip); unlink($path); unlink($tmp); check(hash_file('sha256',BASE_PATH.'/app/Templates/it-pm-inspection.xlsx')===$hash,'original template unchanged');
require_once BASE_PATH.'/vendor/autoload.php';
$pdf=PmInspectionExport::pdf($saved); check(str_starts_with($pdf,'%PDF-'),'PDF produced');
$blank=PmInspection::defaults($schedule); check(str_starts_with(PmInspectionExport::pdf($blank),'%PDF-') && str_starts_with(PmInspectionExport::xlsx($blank),'PK'),'blank planned form exports without assumed dates/results');
if (isset($argv[1]) && is_dir($argv[1])) {
    file_put_contents($argv[1].'/pm-test.xlsx',$bytes); file_put_contents($argv[1].'/pm-test.pdf',$pdf);
    $long=$saved; $long['checks'][13]['notes']=str_repeat('ข้อความยาวทดสอบ ',19); $long['checks'][13]['standard']=str_repeat('มาตรฐานทดสอบ ',12); $long['notes']=str_repeat('หมายเหตุทดสอบ ',30);
    file_put_contents($argv[1].'/pm-long.pdf',PmInspectionExport::pdf($long)); file_put_contents($argv[1].'/pm-long.xlsx',PmInspectionExport::xlsx($long));
    $html=render('pm_records/create',['schedule'=>$schedule,'history'=>[],'old'=>$input]);
    $css=file_get_contents(BASE_PATH.'/public/assets/vendor/bootstrap/css/bootstrap.min.css').file_get_contents(BASE_PATH.'/public/assets/css/style.css').file_get_contents(BASE_PATH.'/public/assets/css/pm-inspection.css');
    $html=preg_replace('~<link rel="stylesheet"[^>]+>~','',$html); $html=preg_replace('~<script src="[^"]+pm-inspection.js" defer></script>~','',$html);
    file_put_contents($argv[1].'/pm-preview.html','<!doctype html><html lang="th"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>IT-F-005 Test Preview</title><style>'.$css.'</style><body><main style="padding:24px">'.$html.'</main><script>'.file_get_contents(BASE_PATH.'/public/assets/js/pm-inspection.js').'</script></body></html>');
}
echo 'PASS IT-F-005: '.($checks-$start)." checks (isolated SQLite; no production records).\n";
