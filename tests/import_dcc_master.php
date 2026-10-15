<?php
// Explicit one-time CLI import. Default is read-only preview; never updates existing rows.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Config/config.php';
require BASE_PATH.'/app/Config/database.php';
foreach (['Model','Validation','DocumentControlInput'] as $class) require BASE_PATH.'/app/Core/'.$class.'.php';
require BASE_PATH.'/app/Models/ControlledDocument.php';
$source = 'C:/Users/thaw0524/AppData/Local/Temp/svp-dcc-master-rows-20261015.json';
$rows = json_decode(file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);
if (count($rows) !== 1446) throw new RuntimeException('Unexpected source row count; review source again');
$db = Database::connect();
$actorQuery = $db->prepare('SELECT svp_user_id FROM users WHERE email=? AND user_role=? AND is_active=1');
$actorQuery->execute(['thawatchai.r@sv-polymer.com','admin']);
$actors=$actorQuery->fetchAll(PDO::FETCH_COLUMN);
if (count($actors)!==1) throw new RuntimeException('Import actor must be unique and active');
$actor=(int)$actors[0];
$mapping=['RM'=>[10,'Raw Material'],'PU'=>[7,'Purchase'],'QC'=>[11,'Quality'],'MN'=>[6,'Maintenance'],'TC'=>[5,'Technical'],'EN'=>[15,'Enviroment'],'SF'=>[14,'Safety'],'IT'=>[2,'Information Technology'],'OP'=>[8,'Operation'],'HR'=>[3,'Human Resource'],'DC'=>[13,'DCC']];
$departments=$db->query('SELECT svp_department_id,svp_department_name FROM department')->fetchAll(PDO::FETCH_KEY_PAIR);
foreach($mapping as [$id,$name]) if(($departments[$id]??null)!==$name) throw new RuntimeException('Department mapping changed');
$old=$db->query('SELECT * FROM controlled_documents ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$oldHistory=$db->query('SELECT * FROM controlled_document_history ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$counts=[];
foreach($rows as $r) { $key=mb_strtolower(trim($r['code'])); $counts[$key]=($counts[$key]??0)+1; }
$exists=$db->prepare('SELECT id FROM controlled_documents WHERE code=?');
$ready=[]; $skipped=[]; $unmapped=0;
foreach($rows as $r) {
    $reason='';
    if($counts[mb_strtolower(trim($r['code']))]>1) $reason='รหัสซ้ำในทะเบียนหลัก';
    elseif(!$r['title'] || $r['revision']==='' || !$r['effective_date']) $reason='ข้อมูลไม่ครบหรือวันที่ไม่ถูกต้อง';
    elseif(!Validation::date($r['effective_date']) || substr($r['effective_date'],0,4)<'1900' || substr($r['effective_date'],0,4)>'2100') $reason='วันที่อยู่นอกช่วงที่ตรวจสอบได้';
    elseif(str_contains(implode(' ', $r['source_notes']), 'ยกเลิก')) $reason='ต้นฉบับระบุยกเลิก ต้องตรวจสอบ';
    $exists->execute([$r['code']]);
    if($exists->fetchColumn()) $reason='มีรหัสในระบบแล้ว ไม่แก้ไขทับ';
    $dept=$mapping[$r['sheet']][0]??null;
    $data=['code'=>$r['code'],'title'=>$r['title'],'revision'=>$r['revision'],'department_id'=>$dept,'status'=>'draft','effective_date'=>$r['effective_date'],'review_date'=>null,'file_item_id'=>'','file_name'=>'','file_url'=>'',
        'notes'=>"นำเข้าทะเบียนหลักเป็นฉบับร่างรอตรวจสอบ ยังไม่ได้เชื่อมไฟล์เอกสาร\nต้นฉบับ: 00. DC-F-002-01 Master List.xlsx\nSharePoint item: 012SR5OU6IN2PAE6BZHRFY3XEABFBZ6UN5\nชีต: {$r['sheet']} แถว: {$r['row']}\nแผนกต้นฉบับ: {$r['sheet']}".($dept===null?"\nรอจับคู่แผนกในระบบ":'')."\nหมายเหตุเดิม: ".implode(' / ',$r['source_notes'])];
    if(!$reason && DocumentControlInput::validate($data)) $reason=implode(' / ',DocumentControlInput::validate($data));
    if($reason) { $skipped[]=['sheet'=>$r['sheet'],'row'=>$r['row'],'code'=>$r['code'],'reason'=>$reason]; continue; }
    if($dept===null) $unmapped++;
    $ready[]=$data;
}
$summary=['source_rows'=>count($rows),'existing_before'=>count($old),'ready'=>count($ready),'skipped'=>count($skipped),'awaiting_department'=> $unmapped,'source_sha256'=>hash_file('sha256',$source)];
echo json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
echo json_encode(array_count_values(array_column($skipped,'reason')),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
if (($argv[1]??'')!=='--apply') exit;
if (($argv[2]??'')!==$summary['source_sha256']) throw new RuntimeException('Source checksum confirmation required');
$dir='C:/Users/thaw0524/AppData/Local/Temp/svp-dcc-import-'.date('Ymd-His').'-'.bin2hex(random_bytes(3));
if(!mkdir($dir,0700)) throw new RuntimeException('Backup directory failed');
$write=static function(string $path,array $data):void { if(file_put_contents($path,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR))===false) throw new RuntimeException('Audit file write failed'); };
$write($dir.'/before.json',['documents'=>$old,'history'=>$oldHistory]);
$report=['summary'=>$summary,'created'=>[],'skipped'=>$skipped];
$write($dir.'/result.json',$report);
$model=new ControlledDocument();
try {
    foreach($ready as $data) {
        $id=$model->saveDocument(null,$data,0,$actor);
        $report['created'][]=['id'=>$id,'code'=>$data['code'],'department_id'=>$data['department_id']];
        $write($dir.'/result.json',$report);
    }
    $get=$db->prepare('SELECT * FROM controlled_documents WHERE id=?');
    foreach($old as $previous) { $get->execute([$previous['id']]); if($get->fetch(PDO::FETCH_ASSOC)!==$previous) throw new RuntimeException('Existing row changed during import'); }
    foreach($report['created'] as $created) {
        $get->execute([$created['id']]); $saved=$get->fetch(PDO::FETCH_ASSOC);
        if(!$saved || $saved['status']!=='draft' || count($model->history($created['id']))!==1) throw new RuntimeException('Verification failed');
    }
    $report['verified']=true;
} finally { $write($dir.'/result.json',$report); }
echo 'Created: '.count($report['created']).PHP_EOL.'Report: '.$dir.'/result.json'.PHP_EOL;
