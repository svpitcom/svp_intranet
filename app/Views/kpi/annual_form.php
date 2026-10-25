<?php
$esc=static fn($v)=>Portal::esc(is_scalar($v)?(string)$v:'');
$field=static function($name,$label,$type='text',$max=500) use($data,$esc) { ?>
<label class="form-label w-100"><?= $esc($label) ?><?php if ($type==='textarea'): ?><textarea class="form-control" name="<?= $name ?>" maxlength="<?= $max ?>" rows="3"><?= $esc($data[$name]??'') ?></textarea><?php else: ?><input class="form-control" type="<?= $type ?>" name="<?= $name ?>" maxlength="<?= $max ?>" value="<?= $esc($data[$name]??'') ?>" <?= in_array($name,['title','year'])?'required':'' ?> <?= $name==='year'?'min="2000" max="2099"':'' ?>><?php endif; ?></label>
<?php };
?>
<div class="d-flex justify-content-between flex-wrap gap-2 mb-3"><div><h1 class="h3">KPI and action plan ของแผนก</h1><span class="text-muted">MR-F-027-01 · แบบรายปี</span></div><div><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/kpi/forms?department_id=<?= (int)($data['department_id']??0) ?: '' ?>">รายการแบบฟอร์ม</a>
<?php if ($row): ?> <a class="btn btn-success" href="<?= APP_URL ?>/kpi/forms/<?= (int)$row['id'] ?>/export?format=xlsx">Export Excel</a> <a class="btn btn-outline-danger" href="<?= APP_URL ?>/kpi/forms/<?= (int)$row['id'] ?>/export?format=pdf">Export PDF</a><div class="small text-muted mt-1">ส่งออกข้อมูลที่บันทึกแล้ว · ฉบับที่ <?= (int)$row['version'] ?></div><?php endif; ?></div></div>
<?php if ($error): ?><div role="alert" class="alert alert-danger"><?= $esc($error) ?></div><?php endif; ?>
<?php if ($row): ?>
<section class="card mb-3" aria-labelledby="kpi-sharepoint-heading"><div class="card-body">
<h2 class="h5" id="kpi-sharepoint-heading">ส่งไป SharePoint</h2>
<?php if ($canSend): ?>
<form id="kpi-sharepoint-form" method="post" action="<?= APP_URL ?>/kpi/forms/<?= (int)$row['id'] ?>/sharepoint" class="d-flex flex-wrap gap-3 align-items-end">
<input type="hidden" name="_csrf" value="<?= Portal::esc(Session::csrfToken()) ?>"><input type="hidden" name="version" value="<?= (int)$row['version'] ?>">
<label class="form-label mb-0">รูปแบบไฟล์<select class="form-select" name="format"><option value="xlsx">Excel (.xlsx)</option><option value="pdf">PDF (.pdf)</option></select></label>
<label class="form-label mb-0">โฟลเดอร์ปลายทาง<select class="form-select" name="destination" required><option value="">เลือกโฟลเดอร์ SharePoint</option><?php foreach($destinations as $key=>$label): ?><option value="<?= $esc($key) ?>"><?= $esc($label) ?></option><?php endforeach; ?></select></label>
<button class="btn btn-primary" <?= !$destinations?'disabled':'' ?>>ส่งฉบับที่ <?= (int)$row['version'] ?> ไป SharePoint</button>
</form>
<p class="small text-muted mt-2 mb-0">ส่งข้อมูลที่บันทึกแล้ว หากแก้ไขช่องกรอกให้บันทึกก่อนส่ง · ส่งซ้ำฉบับและชนิดเดิมจะอัปเดตไฟล์ชื่อเดิมที่ปลายทาง</p>
<p id="kpi-sharepoint-unsaved" class="small text-warning mb-0" role="status" hidden>มีการแก้ไขที่ยังไม่ได้บันทึก กรุณาบันทึกแบบฟอร์มก่อนส่ง</p>
<?php if (!$destinations): ?><p class="text-warning mb-0">ยังไม่มีปลายทางพร้อมใช้งาน กรุณาตรวจการตั้งค่าที่เมนู SharePoint</p><?php endif; ?>
<?php else: ?><p class="text-muted">ผู้ดูแลระบบสามารถส่งฉบับที่บันทึกแล้วไป SharePoint ได้</p><?php endif; ?>
<?php if ($deliveries): ?><div class="table-responsive mt-3"><table class="table table-sm"><thead><tr><th>ฉบับ</th><th>ไฟล์</th><th>ปลายทาง</th><th>ส่งเมื่อ</th><th></th></tr></thead><tbody>
<?php foreach($deliveries as $delivery): $url=$delivery['web_url']; $safeUrl=is_string($url) && filter_var($url,FILTER_VALIDATE_URL) && strtolower(parse_url($url,PHP_URL_SCHEME)??'')==='https' && !parse_url($url,PHP_URL_USER) && !parse_url($url,PHP_URL_PASS); ?>
<tr><td><?= (int)$delivery['version'] ?><?= (int)$delivery['version']!==(int)$row['version']?' (ฉบับก่อนหน้า)':'' ?></td><td><?= $esc($delivery['file_name']) ?></td><td><?= $esc($delivery['destination']==='default'?'โฟลเดอร์ส่งออกหลัก':$delivery['destination']) ?></td><td><?= $esc($delivery['sent_at']) ?></td><td><?php if($safeUrl): ?><a href="<?= $esc($url) ?>" target="_blank" rel="noopener noreferrer">เปิดใน SharePoint</a><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?>
</div></section>
<?php endif; ?>
<form id="kpi-edit-form" method="post" action="<?= APP_URL ?>/kpi/forms/<?= $row?(int)$row['id']:'create' ?>">
<input type="hidden" name="_csrf" value="<?= Portal::esc(Session::csrfToken()) ?>"><input type="hidden" name="version" value="<?= $esc($data['version']??0) ?>">
<fieldset <?= $editable?'':'disabled' ?>><div class="card mb-3"><div class="card-body row g-3">
<div class="col-md-8"><?php $field('title','หัวข้อ KPI','text',200); ?></div><div class="col-md-4"><?php $field('year','Year (ค.ศ.)','number'); ?></div>
<div class="col-md-4"><label class="form-label w-100">แผนก/ฝ่าย<select class="form-select" name="department_id" required><?php foreach ($departments as $dep): ?><option value="<?= (int)$dep['svp_department_id'] ?>" <?= (string)($data['department_id']??'')===(string)$dep['svp_department_id']?'selected':'' ?>><?= $esc($dep['svp_department_name']) ?></option><?php endforeach; ?></select></label></div>
<div class="col-md-4"><?php $field('owner','ผู้รับผิดชอบ'); ?></div><div class="col-md-4"><div>วัตถุประสงค์ (objective)</div><?php foreach (['qms'=>'Quality (QMS)','ems'=>'Environment (EMS)'] as $k=>$label): ?><label class="form-check"><input class="form-check-input" type="checkbox" name="<?= $k ?>" value="1" <?= !empty($data[$k])?'checked':'' ?>><?= $label ?></label><?php endforeach; ?></div>
<?php foreach (['effective_date'=>'Effective date','prepared_by'=>'Prepared by','approved_by'=>'Approved by'] as $k=>$label): ?><div class="col-md-4"><?php $field($k,$label,$k==='effective_date'?'date':'text'); ?></div><?php endforeach; ?>
<p class="small text-muted mb-0">Approved by เป็นช่องระบุชื่อในเอกสาร ไม่ใช่การอนุมัติผ่านระบบหรือลายเซ็นดิจิทัล</p>
</div></div>
<div class="row g-3"><div class="col-lg-3"><div class="card h-100"><div class="card-body"><?php foreach (['target'=>'Target','measurement'=>'วิธีวัดผล','frequency'=>'ความถี่'] as $k=>$label) $field($k,$label,'textarea'); ?></div></div></div>
<div class="col-lg-9"><div class="card"><div class="card-body"><h2 class="h5">Activity / action plan</h2><div class="table-responsive"><table class="table table-bordered align-middle" style="min-width:1050px"><thead><tr><th rowspan="2">Items</th><th rowspan="2" style="min-width:220px">Activity/action plan</th><th rowspan="2" style="min-width:140px">ผู้รับผิดชอบ</th><th rowspan="2" style="min-width:140px">ทรัพยากรที่ใช้</th><th colspan="12">แผนงาน (เดือน)</th></tr><tr><?php for($m=1;$m<=12;$m++): ?><th><?= $m ?></th><?php endfor; ?></tr></thead><tbody>
<?php for($i=0;$i<11;$i++): $a=is_array($data['activities'][$i]??null)?$data['activities'][$i]:[]; ?><tr><td><?= $i+1 ?></td><?php foreach (['activity'=>'กิจกรรม','owner'=>'ผู้รับผิดชอบ','resources'=>'ทรัพยากร'] as $k=>$label): ?><td><textarea class="form-control" rows="2" maxlength="300" name="activities[<?= $i ?>][<?= $k ?>]" aria-label="<?= $label ?> แถว <?= $i+1 ?>"><?= $esc($a[$k]??'') ?></textarea></td><?php endforeach; ?><?php for($m=1;$m<=12;$m++): ?><td><input class="form-check-input" type="checkbox" name="activities[<?= $i ?>][months][]" value="<?= $m ?>" aria-label="แถว <?= $i+1 ?> เดือน <?= $m ?>" <?= in_array($m,is_array($a['months']??null)?$a['months']:[])?'checked':'' ?>></td><?php endfor; ?></tr><?php endfor; ?>
</tbody></table></div></div></div></div></div>
<div class="row g-3 my-1"><div class="col-lg-5"><div class="card h-100"><div class="card-body"><?php $field('action_taken','Action taken (กรณี Target ไม่เป็นไปตามเป้าหมาย)','textarea',2000); ?></div></div></div>
<div class="col-lg-3"><div class="card"><div class="card-body"><h2 class="h5">ผลรายเดือน</h2><p class="small text-muted">ใช้หน่วยเดียวกับ Target เช่น ร้อยละกรอก 95 หมายถึง 95% · เว้นว่างเมื่อยังไม่มีผล</p><?php for($i=0;$i<12;$i++): ?><label class="d-flex gap-2 align-items-center mb-2"><span style="width:45px"><?= date('M',mktime(0,0,0,$i+1,1,2026)) ?></span><input class="form-control result-input" type="number" step="0.0001" min="-9999999999.9999" max="9999999999.9999" name="results[<?= $i ?>][result]" value="<?= $esc($data['results'][$i]['result']??'') ?>"></label><?php endfor; ?><?php $field('ytd','YTD'); ?><p class="small text-muted">กรอก YTD ตามวิธีวัดผล ไม่รวมอัตโนมัติ</p></div></div></div>
<div class="col-lg-4"><div class="card"><div class="card-body"><h2 class="h5">Monitoring</h2><div id="monitoring-chart" aria-label="กราฟผลรายเดือน"></div></div></div></div></div>
<?php if ($editable): ?><button class="btn btn-primary my-3" type="submit">บันทึกแบบฟอร์ม</button><?php endif; ?></fieldset></form>
<script>
(() => {
 const editForm=document.getElementById('kpi-edit-form'), sendForm=document.getElementById('kpi-sharepoint-form');
 if(sendForm){
  const button=sendForm.querySelector('button'), note=document.getElementById('kpi-sharepoint-unsaved'), unavailable=button.disabled;
  const snapshot=()=>new URLSearchParams(new FormData(editForm)).toString(), saved=snapshot(); let sending=false;
  function updateSend(){const dirty=snapshot()!==saved; button.disabled=unavailable || dirty || sending; note.hidden=!dirty; return dirty;}
  editForm.addEventListener('input',updateSend); editForm.addEventListener('change',updateSend);
  sendForm.addEventListener('submit',event=>{if(updateSend() || unavailable || sending){event.preventDefault();return;} sending=true; button.disabled=true; button.textContent='กำลังส่งไป SharePoint…';});
 }
 const inputs=[...document.querySelectorAll('.result-input')], chart=document.getElementById('monitoring-chart');
 function draw(){const vals=inputs.map(e=>e.value===''?null:Number(e.value)), known=vals.filter(v=>v!==null && Number.isFinite(v)); const low=Math.min(0,...known), high=Math.max(low+1,...known); let svg='<svg viewBox="0 0 480 240" role="img" aria-label="กราฟผลรายเดือน" style="width:100%;background:white">'; for(let i=0;i<=4;i++){let y=20+i*43;svg+=`<path d="M65 ${y} H465" stroke="#ddd"/><text x="2" y="${y+4}" font-size="11">${Number((high-(high-low)*i/4).toFixed(2))}</text>`;} let prev=null;vals.forEach((v,i)=>{let x=70+i*35;svg+=`<text x="${x-5}" y="215" font-size="11">${i+1}</text>`;if(v===null || !Number.isFinite(v)){prev=null;return;}let y=20+(high-v)/(high-low)*172;if(prev)svg+=`<path d="M${prev[0]} ${prev[1]} L${x} ${y}" stroke="#176b88" fill="none" stroke-width="2"/>`;svg+=`<circle cx="${x}" cy="${y}" r="3" fill="#176b88"/>`;prev=[x,y];});chart.innerHTML=svg+'</svg>';}
 inputs.forEach(e=>e.addEventListener('input',draw));draw();
})();
</script>
