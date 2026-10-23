<?php
if (PHP_SAPI!=='cli') exit;
require __DIR__.'/kpi.php';
$start=$checks;
$db->exec('CREATE TABLE kpi_forms(id INTEGER PRIMARY KEY AUTOINCREMENT,department_id INTEGER,user_id INTEGER,year INTEGER,title TEXT,payload TEXT,version INTEGER,updated_by INTEGER,updated_at TEXT)');
$db->exec('CREATE TABLE kpi_form_history(id INTEGER PRIMARY KEY AUTOINCREMENT,form_id INTEGER,payload TEXT,version INTEGER,actor_id INTEGER,created_at TEXT,UNIQUE(form_id,version))');
$input=['title'=>'ทดสอบ KPI <script> & =1+1','department_id'=>'2','year'=>'2026','owner'=>'ผู้รับผิดชอบทดสอบ','prepared_by'=>'ผู้จัดทำ','approved_by'=>'ผู้อนุมัติ','effective_date'=>'2026-01-01','qms'=>'1','target'=>'สำเร็จอย่างน้อย 95%','measurement'=>'งานสำเร็จ / งานทั้งหมด × 100','frequency'=>'รายเดือน','action_taken'=>'ตรวจสอบสาเหตุและติดตามผลในเดือนถัดไป','ytd'=>'95.125','activities'=>[['activity'=>'ตรวจสอบและปรับปรุงกระบวนการ','owner'=>'ฝ่าย IT','resources'=>'ทีมงาน','months'=>['1','3','12']]],'results'=>[['result'=>'0'],['result'=>'95.125'],['result'=>''],['result'=>'-1.25']]];
$form=new KpiForm(); $id=$form->save(null,$manager,$input);
$row=$form->detail($id,$worker);
check($row['data']['department']==='IT','department stored in document');
check(count($row['data']['activities'])===11 && count($row['data']['results'])===12,'template row counts');
check($row['data']['results'][0]['result']==='0' && $row['data']['results'][2]['result']==='','zero distinct from missing');
rejectsKpi(fn()=>$form->detail($id,$other),'other department cannot read/export');
rejectsKpi(fn()=>$form->save($id,$worker,$input+['version'=>'1']),'ordinary reader cannot modify');
rejectsKpi(fn()=>$form->save(null,$manager,array_replace($input,['department_id'=>'6'])),'cannot create other department form');
$form->save($id,$manager,$input+['version'=>'1']);
rejectsKpi(fn()=>$form->save($id,$manager,$input+['version'=>'1']),'stale document rejected');
check((int)$db->query('SELECT COUNT(*) FROM kpi_form_history')->fetchColumn()===2,'one history snapshot per successful save');
check((int)$form->find($id)['version']===2,'conflict preserves current version');
check(count($form->listing($other))===0,'other department list empty');
rejectsKpi(fn()=>$form->listing(array_replace($manager,['is_active'=>0])),'inactive cannot list');
rejectsKpi(fn()=>$form->detail($id,array_replace($manager,['svp_department_id'=>13])),'DCC cannot access form');
foreach (['2026-02-30','invalid'] as $date) rejectsKpi(fn()=>KpiForm::clean(array_replace($input,['effective_date'=>$date])),'invalid date');
rejectsKpi(fn()=>KpiForm::clean(array_replace($input,['year'=>'1999'])),'invalid year');
rejectsKpi(fn()=>KpiForm::clean(array_replace($input,['results'=>[['result'=>'=1+1']]])),'result formula rejected');
rejectsKpi(fn()=>KpiForm::clean(array_replace($input,['activities'=>[['months'=>['13']]]])),'invalid month');
rejectsKpi(fn()=>KpiForm::clean(array_replace($input,['activities'=>[['months'=>['1']]]])),'orphan schedule month rejected');
$before=hash_file('sha256',BASE_PATH.'/app/Templates/kpi-action-plan.xlsx');
$bytes=KpiFormExport::xlsx($row['data']);
check(substr($bytes,0,2)==='PK','XLSX ZIP produced');
$temp=tempnam(sys_get_temp_dir(),'kpi-test-'); $path=$temp.'.zip'; file_put_contents($path,$bytes);
$zip=new PharData($path);
$xml=$zip['xl/worksheets/sheet1.xml']->getContent(); $doc=new DOMDocument(); check($doc->loadXML($xml),'worksheet XML valid');
$xp=new DOMXPath($doc); $xp->registerNamespace('x','http://schemas.openxmlformats.org/spreadsheetml/2006/main');
check($xp->query('//x:c[@r="I18"]')->item(0)->getAttribute('t')==='n','zero numeric in Excel');
check($xp->query('//x:c[@r="I19"]/x:v')->item(0)->textContent==='95.125','fraction retained');
check($xp->query('//x:c[@r="J1"]/x:is/x:t')->item(0)->textContent===$input['title'],'literal text and XML characters retained');
check($xp->query('//x:mergeCell[@ref="A18:G30"]')->length===1,'action field merge');
$chart=$zip['xl/charts/chart1.xml']->getContent();
check(str_contains($chart,'val="gap"') && str_contains($chart,'95.125'),'chart cache and missing-value gaps');
check(hash_file('sha256',BASE_PATH.'/app/Templates/kpi-action-plan.xlsx')===$before,'original template unchanged');
unset($zip); unlink($path); unlink($temp);
require_once BASE_PATH.'/vendor/autoload.php';
$pdf=KpiFormExport::pdf($row['data']); check(substr($pdf,0,5)==='%PDF-','PDF generated');
class KpiFormViewHarness extends KpiFormController
{
    protected function view(string $view,array $data=[],string $layout='main'): void { extract($data); require BASE_PATH.'/app/Views/'.$view.'.php'; }
}
$_SESSION['user']=$manager;
ob_start(); (new KpiFormViewHarness())->edit((string)$id); $html=ob_get_clean();
check(str_contains($html,'Export Excel') && str_contains($html,'Export PDF'),'both export controls');
check(substr_count($html,'class="form-check-input" type="checkbox" name="activities[')===132,'all 11 by 12 plan controls');
check(str_contains($html,'&lt;script&gt;') && !str_contains($html,'<script> & =1+1'),'form escapes title');
$_SESSION['user']=$worker; ob_start(); (new KpiFormViewHarness())->edit((string)$id); $readOnly=ob_get_clean();
check(str_contains($readOnly,'<fieldset disabled>'),'reader cannot edit through form');
$_SESSION['user']=$other; ob_start(); (new KpiFormViewHarness())->export((string)$id); ob_end_clean();
check(http_response_code()===403,'export endpoint enforces department'); http_response_code(200);
// Optional local artifacts for visual inspection; never write fixtures into the application database.
if (isset($argv[1]) && is_dir($argv[1])) { file_put_contents($argv[1].'/kpi-test.xlsx',$bytes); file_put_contents($argv[1].'/kpi-test.pdf',$pdf); file_put_contents($argv[1].'/kpi-form-preview.html','<!doctype html><html lang="th"><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="http://localhost/svp_intranet/public/assets/vendor/bootstrap/css/bootstrap.min.css"><body><main style="padding:24px">'.$html.'</main></body></html>'); }
echo 'PASS KPI annual form: '.($checks-$start)." checks\n";
