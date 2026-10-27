<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
ob_start(); require __DIR__.'/repair_access.php'; $start=$checks;
$input=array_replace($data,$machineRequestInput,['category'=>'machine','requester_name'=>'FORGED','access_date'=>$fixtureDate,'access_time'=>'09:00']);
$case=$work->createItem('ticket',$input,$dcc);
$item=$work->detail($case,$dcc); $form=$item['machine_form'];
check($form['request_data']['machine_name']==='TEST-MACHINE' && $form['request_data']['request_priority']==='P2','MN request fields persist');
check($form['request_data']['requester_name']!=='FORGED' && $item['device_id']===null,'requester comes from session and machine never links IT device');
check($item['priority']==='high' && $item['due_date']===$fixtureDate,'template priority and access date map to work listing');
denied(fn()=>$work->createItem('ticket',array_replace($input,['machine_name'=>'']),$dcc),'new machine report requires machine');
denied(fn()=>$work->createItem('ticket',array_replace($input,['report_time'=>'25:00']),$dcc),'invalid report time denied');
denied(fn()=>$work->createItem('ticket',array_replace($input,['access_time'=>'']),$dcc),'access date and time paired');
denied(fn()=>$work->createItem('ticket',array_replace($input,['request_priority'=>'P9']),$dcc),'unknown priority denied');
$_SESSION['user']=$dcc; $_GET=$_POST=$_FILES=[];
ob_start(); $router->dispatch('GET','/tickets/create'); $html=ob_get_clean();
foreach (MachineRepairForm::REQUEST_FIELDS as $field) check(str_contains($html,'name="'.$field.'"'),'request UI includes '.$field);
check(str_contains($html,'accept="image/jpeg,image/png,image/webp"') && !str_contains($html,'accept="application/pdf"'),'repair form accepts photos');
check(str_contains($html,'repair-photo-preview'),'upload preview exists');
$_POST=array_replace($input,['_csrf'=>Session::csrfToken(),'priority'=>'']);
try { $router->dispatch('POST','/tickets/create'); } catch(RedirectSignal $e) {}
$postedId=(int)$db->query('SELECT MAX(id) FROM work_items')->fetchColumn();
check($postedId>$case && $work->detail($postedId,$dcc)['machine_form']['request_data']['machine_name']==='TEST-MACHINE','machine POST works when generic priority field disabled');
$_POST=[];
$work->act($case,$maintenance,'claim',1,'',null);
denied(fn()=>$work->act($case,$dcc,'save_repair',2,'',null,$machineRepairInput),'requester cannot write technician section');
$repair=array_replace($machineRepairInput,['parts'=>[['code'=>'ST-001','description'=>'Bearing <script>x</script>','quantity'=>'2.5','unit'=>'ชิ้น','note'=>'ทดสอบ']],'technicians'=>['A','B','C','D','E','F']]);
$work->act($case,$maintenance,'save_repair',2,'',null,$repair);
check($work->detail($case,$maintenance)['status']==='in_progress','saving repair draft does not complete work');
check($work->detail($case,$maintenance)['machine_form']['repair_data']['duration_minutes']===60,'repair duration calculated');
denied(fn()=>$work->act($case,$maintenance,'resolve',2,'',null,$repair),'stale repair update denied');
denied(fn()=>$work->act($case,$maintenance,'resolve',3,'',null,array_replace($repair,['end_time'=>'08:00'])),'negative duration rejected');
denied(fn()=>$work->act($case,$maintenance,'resolve',3,'',null,array_replace($repair,['parts'=>[['description'=>'Part','quantity'=>'-1','unit'=>'pc']]])),'negative spare quantity rejected');
denied(fn()=>$work->act($case,$maintenance,'resolve',3,'',null,array_replace($repair,['technicians'=>array_fill(0,7,'A')])),'more than six technicians rejected');
denied(fn()=>$work->act($case,$maintenance,'resolve',3,'',null,array_replace($repair,['parts'=>array_fill(0,8,[])])),'more than seven parts rejected');
check((int)$work->detail($case,$maintenance)['version']===3,'validation failures leave version unchanged');
$_SESSION['user']=$maintenance;
$_POST=array_replace($repair,['_csrf'=>Session::csrfToken(),'action'=>'resolve','version'=>'3','end_time'=>'08:00']);
try { $router->dispatch('POST','/work/'.$case.'/action'); } catch(RedirectSignal $e) {}
check(Session::get('work_form_input')['data']['end_time']==='08:00','failed repair input retained without flash type error');
ob_start(); $router->dispatch('GET','/work/'.$case); $failedHtml=ob_get_clean();
check(str_contains($failedHtml,'value="08:00"'),'failed input shown for correction');
$_POST=[];
$work->act($case,$maintenance,'resolve',3,'',null,$repair);
check($work->detail($case,$dcc)['status']==='resolved','complete machine repair waits for acceptance');
$_SESSION['user']=$dcc; ob_start(); $router->dispatch('GET','/work/'.$case); $html=ob_get_clean();
check(str_contains($html,'Bearing &lt;script&gt;x&lt;/script&gt;') && !str_contains($html,'Bearing <script>'),'parts are displayed safely');
foreach (MachineRepairForm::ACCEPTANCE_FIELDS as $field) check(str_contains($html,'name="'.$field.'"'),'acceptance UI includes '.$field);
$acceptance=['acceptance_result'=>'temporary','inspection_date'=>Portal::today(),'suggestions'=>'ยังมีเสียงดัง'];
denied(fn()=>$work->act($case,$dcc,'close',4,'',null,$acceptance),'temporary result cannot falsely close case');
$work->act($case,$dcc,'reopen',4,'',null,$acceptance);
check($work->detail($case,$dcc)['status']==='in_progress','temporary acceptance returns case for repair');
$work->act($case,$maintenance,'resolve',5,'',null,array_replace($repair,['repair_detail'=>'แก้ไขรอบสอง']));
$work->act($case,$dcc,'close',6,'',null,['acceptance_result'=>'normal','inspection_date'=>Portal::today(),'suggestions'=>'','inspector_name'=>'FORGED']);
$item=$work->detail($case,$dcc);
check($item['status']==='closed' && $item['machine_form']['acceptance_data']['inspector_id']===24,'acceptance records actual signed-in inspector');
check($item['machine_form']['acceptance_data']['inspector_name']!=='FORGED','inspector cannot be spoofed');
$snapshots=array_values(array_filter($work->history($case),fn($e)=>$e['action']==='form_snapshot'));
check(count($snapshots)===5 && str_contains($snapshots[2]['body'],'temporary'),'repair and earlier acceptance snapshots preserved');
$safe=MachineRepairForm::displayInput(['parts'=>[['description'=>['bad']]],'technicians'=>[['bad']]]);
check($safe['parts'][0]['description']==='' && $safe['technicians'][0]==='','malformed failed input safe to redisplay');
// Legacy machine records remain accessible without inventing historical form data.
$db->exec('DELETE FROM work_machine_forms WHERE work_id='.$machineTicket);
check($work->detail($machineTicket,$maintenance)['machine_form']===null,'legacy machine work still loads');
$work->act($machineTicket,$maintenance,'start',2,'',null);
$work->act($machineTicket,$maintenance,'resolve',3,'legacy resolution',null);
check($work->detail($machineTicket,$owner)['status']==='resolved','legacy work retains original resolution workflow');
// Inspect real file content independently of browser-provided names.
$folder=sys_get_temp_dir().'/svp-image-test-'.bin2hex(random_bytes(6)); mkdir($folder);
$name=str_repeat('a',32).'.png';
try {
    file_put_contents($folder.'/'.$name,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a7WQAAAAASUVORK5CYII='));
    check(FileUploader::imageInfo($folder.'/'.$name)['mime']==='image/png','real PNG accepted');
    check(FileUploader::resolveImage($folder,$name)!==null,'safe image path resolves');
    check(FileUploader::resolveImage($folder,'../'.$name)===null,'image traversal rejected');
    check(FileUploader::resolve($folder,$name)===null,'PDF resolver unchanged');
    file_put_contents($folder.'/fake.png','<?php echo "not a photo";');
    denied(fn()=>FileUploader::imageInfo($folder.'/fake.png'),'renamed script rejected');
    file_put_contents($folder.'/fake.png','<svg xmlns="http://www.w3.org/2000/svg"></svg>');
    denied(fn()=>FileUploader::imageInfo($folder.'/fake.png'),'SVG rejected');
    $_FILES=['attachment'=>['error'=>UPLOAD_ERR_OK,'tmp_name'=>$folder.'/'.$name,'name'=>'x.png']];
    denied(fn()=>FileUploader::uploadImage('attachment',$folder),'non HTTP file cannot bypass uploaded-file validation');
    FileUploader::discardImage($folder,$name); check(!file_exists($folder.'/'.$name),'unused uploaded image can be cleaned up');
} finally { $_FILES=[]; foreach ([$name,'fake.png'] as $file) if (is_file($folder.'/'.$file)) unlink($folder.'/'.$file); rmdir($folder); }
echo 'PASS: '.($checks-$start)." machine repair form and image checks (isolated fixtures).\n";
ob_end_flush();
