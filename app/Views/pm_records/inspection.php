<?php
$readOnly=$readOnly??false;
$e=static fn($v)=>Portal::esc((string)$v);
$field=static function(string $key,string $label,string $type='text',bool $required=false,int $max=150) use($data,$e): void { ?>
    <div class="col-md-4"><label class="form-label" for="pm-<?= $e($key) ?>"><?= $e($label) ?><?= $required?' *':'' ?></label>
    <input class="form-control" id="pm-<?= $e($key) ?>" name="<?= $e($key) ?>" type="<?= $type ?>" value="<?= $e($data[$key]??'') ?>" maxlength="<?= $max ?>" <?= $required?'required':'' ?> <?= $type==='date'?'max="'.Portal::today().'"':'' ?>></div>
<?php };
$rows=static function(string $section,array $numbers) use($data,$e): void {
    $fields=PmInspection::SECTIONS[$section][3]; ?>
    <div class="table-responsive pm-inspection-scroll" tabindex="0" aria-label="ตาราง <?= $e(PmInspection::SECTIONS[$section][0]) ?>"><table class="table pm-inspection-table align-middle mb-0"><thead><tr><th class="pm-row-num">แถว</th><?php foreach($fields as $key=>$label): ?><th class="pm-col-<?= $key ?>"><?= $e($label) ?></th><?php endforeach; ?></tr></thead><tbody>
    <?php foreach($numbers as $r): ?><tr><td class="text-muted text-center"><?= $r ?></td><?php foreach($fields as $key=>$label):
        $value=$data[$section][$r][$key]??''; $name=$section.'['.$r.']['.$key.']'; $options=PmInspection::options($section,$key);
        $fixed=$section==='checks' && isset(PmInspection::MAIN[$r]) && ($key==='topic' || ($key==='method' && PmInspection::MAIN[$r][1]!=='')); ?>
        <td><?php if($options!==null): ?><select class="form-select form-select-sm" name="<?= $e($name) ?>" aria-label="<?= $e($label.' แถว '.$r) ?>"><option value="">— ยังไม่ระบุ —</option><?php foreach($options as $option=>$text): ?><option value="<?= $option ?>" <?= $value===$option?'selected':'' ?>><?= $text ?></option><?php endforeach; ?></select>
        <?php else: ?><textarea class="form-control form-control-sm" name="<?= $e($name) ?>" rows="2" maxlength="<?= $key==='notes'?300:160 ?>" aria-label="<?= $e($label.' แถว '.$r) ?>" <?= $fixed?'readonly':'' ?>><?= $e($value) ?></textarea><?php endif; ?></td>
    <?php endforeach; ?></tr><?php endforeach; ?></tbody></table></div>
<?php }; ?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/pm-inspection.css">
<div class="pm-inspection">
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start mb-4">
        <div><span class="badge bg-primary mb-2">IT-F-005 · PM อุปกรณ์ IT</span><h4 class="mb-1"><?= PmInspection::TITLE ?></h4><p class="text-muted mb-0">SV POLYMER CO., LTD. · <?= $e($data['pm_title']??$schedule['pm_title']??'') ?></p></div>
        <div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/pm-schedules">แผน PM</a><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/pm-records">ประวัติ PM</a>
        <?php if($readOnly): ?><a class="btn btn-primary" href="<?= APP_URL ?>/pm-records/<?= $recordId ?>/form/export?format=xlsx">Export Excel</a><a class="btn btn-outline-danger" href="<?= APP_URL ?>/pm-records/<?= $recordId ?>/form/export?format=pdf">Export PDF</a><?php endif; ?></div>
    </div>
    <?php if($readOnly): ?><div class="alert alert-success">แบบฟอร์มที่บันทึกแล้ว · วันที่ <?= $e($data['performed_date']) ?> · <?= $e(['completed'=>'ทำครบ / ปกติดี','partial'=>'ทำได้บางส่วน','issue_found'=>'พบปัญหา'][$data['result_status']]??'') ?></div>
    <?php else: ?><div class="alert alert-info">บันทึกผลที่ตรวจจริงในแต่ละข้อ หากเลือก “ไม่มี” ให้เว้นผลตรวจไว้ รายการผิดปกติหรือยังทำไม่ครบจะไม่เลื่อนกำหนด PM ครั้งถัดไป</div><?php endif; ?>
    <form method="post" action="<?= $readOnly?'#':APP_URL.'/pm-schedules/'.(int)$schedule['pm_schedule_id'].'/record' ?>" enctype="multipart/form-data" id="pm-inspection-form">
    <?php if(!$readOnly): ?><input type="hidden" name="_csrf" value="<?= $e(Session::csrfToken()) ?>"><input type="hidden" name="inspection_device_id" value="<?= $e($data['inspection_device_id']) ?>"><p class="small">ดาวน์โหลดแบบฟอร์มเปล่า: <a href="<?= APP_URL ?>/pm-schedules/<?= (int)$schedule['pm_schedule_id'] ?>/export?format=xlsx">Excel</a> · <a href="<?= APP_URL ?>/pm-schedules/<?= (int)$schedule['pm_schedule_id'] ?>/export?format=pdf">PDF</a></p><?php endif; ?>
    <fieldset <?= $readOnly?'disabled':'' ?>>
    <section class="card p-4 mb-4"><h5 class="mb-3">ข้อมูลการตรวจสอบ</h5><div class="row g-3">
        <?php $field('device_name','ชื่อเครื่องคอมพิวเตอร์','text',true); $field('serial_number','หมายเลขเครื่อง / Serial No.','text',false,100); $field('department','แผนก / ฝ่าย');
        $field('performed_date','วันที่ตรวจสอบ','date',true); $field('start_time','เวลาเริ่ม (วันเดียวกัน)','time',true); $field('end_time','เวลาสิ้นสุด','time',true);
        $field('manager','ผู้จัดการแผนกไอที','text',false,120); $field('inspector','ผู้รับผิดชอบการตรวจสอบ','text',true,120); $field('acknowledged_by','ผู้ใช้งานรับทราบ (ชื่อ)','text',false,120); ?>
    </div><div class="mt-3 d-flex flex-wrap gap-3"><strong id="pm-duration" aria-live="polite">รวมเวลา: <?= $data['start_time']!=='' && $data['end_time']!==''?PmInspection::minutes($data['start_time'],$data['end_time']).' นาที':'—' ?></strong><span class="text-muted small">ชื่อผู้จัดการและผู้รับทราบเป็นข้อมูลในเอกสาร ไม่ใช่การลงนามหรืออนุมัติผ่านระบบ</span></div></section>
    <?php if(!$readOnly && !empty($schedule['checklist'])): ?><details class="card p-3 mb-4"><summary>รายการอ้างอิงเดิมของแผน PM</summary><div class="mt-2" style="white-space:pre-line"><?= $e($schedule['checklist']) ?></div></details><?php endif; ?>
    <section class="card mb-4"><div class="p-4"><h5>1. หัวข้อที่ทำการตรวจสอบ</h5><p class="text-muted mb-0">7 หัวข้อจากแบบต้นฉบับ แยกงานทำความสะอาดเป็น 3 รายการตรวจ</p></div>
    <?php $rows('checks',array_keys(PmInspection::MAIN)); $extra=array_values(array_diff(range(12,50),array_keys(PmInspection::MAIN)));
    if($readOnly) $extra=array_values(array_filter($extra,static fn($r)=>implode('',$data['checks'][$r])!=='')); ?>
    <?php if($extra): ?><details class="p-3" <?= $readOnly?'open':'' ?>><summary>รายการตรวจเพิ่มเติม (<?= count($extra) ?> แถวตามต้นฉบับ)</summary><?php $rows('checks',$extra); ?></details><?php endif; ?></section>
    <section class="card mb-4"><div class="p-4"><h5 class="mb-0">2. รายละเอียดการดำเนินงาน</h5></div><?php $rows('work',range(53,57)); ?></section>
    <section class="card mb-4"><div class="p-4"><h5 class="mb-0">3. การตรวจสอบด้านความปลอดภัย</h5></div><?php $rows('safety',range(60,62)); $extraSafety=range(63,77); if($readOnly) $extraSafety=array_values(array_filter($extraSafety,static fn($r)=>implode('',$data['safety'][$r])!=='')); ?><?php if($extraSafety): ?><details class="p-3" <?= $readOnly?'open':'' ?>><summary>รายการความปลอดภัยเพิ่มเติม (<?= count($extraSafety) ?> แถว)</summary><?php $rows('safety',$extraSafety); ?></details><?php endif; ?></section>
    <section class="card p-4 mb-4"><h5>สรุปผลการทำ PM</h5><label class="form-label" for="pm-result">ผลรวม *</label><select id="pm-result" name="result_status" class="form-select mb-3" required><option value="">— เลือกผลการทำ PM —</option><?php foreach(['completed'=>'ทำครบตามรายการ / ปกติดี','partial'=>'ทำได้บางส่วน','issue_found'=>'พบปัญหา ต้องติดตามแก้ไข'] as $key=>$label): ?><option value="<?= $key ?>" <?= ($data['result_status']??'')===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select>
    <label class="form-label" for="pm-notes">หมายเหตุเพิ่มเติม (500 ตัวอักษร / ไม่เกิน 9 บรรทัด)</label><textarea id="pm-notes" name="notes" class="form-control" rows="3" maxlength="500"><?= $e($data['notes']??'') ?></textarea>
    <?php if(!$readOnly): ?><label for="pm-attachment" class="form-label mt-3">แนบรายงานเพิ่มเติม (PDF)</label><input id="pm-attachment" class="form-control" type="file" name="pm_attachment" accept="application/pdf"><p class="form-text mb-0">ไม่เกิน 10 MB · PDF ที่แนบจะใช้การส่ง SharePoint ตามระบบเดิม ส่วนแบบฟอร์มนี้ดาวน์โหลด Excel/PDF ได้หลังบันทึก</p><?php endif; ?></section>
    </fieldset>
    <?php if(!$readOnly): ?><div class="pm-inspection-actions"><span class="text-muted small" id="pm-save-hint" aria-live="polite">กรุณาตรวจผลก่อนบันทึก</span><div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/pm-schedules">ยกเลิก</a><button class="btn btn-success" type="submit">บันทึกผลตรวจ PM</button></div></div><?php endif; ?>
    </form><p class="text-muted small mt-3">Control Copy Lay Out : IT · <?= $e($data['revision']??PmInspection::REVISION) ?></p>
    <?php if(!empty($history)): ?><section class="card p-4 mt-4"><h5>ประวัติของแผนนี้</h5><?php foreach($history as $item): ?><div class="border-bottom py-2"><?= $e($item['performed_date'].' · '.$item['first_name'].' '.$item['last_name']) ?> · <?= $e(['completed'=>'ปกติดี','partial'=>'ทำได้บางส่วน','issue_found'=>'พบปัญหา'][$item['result_status']]??'') ?><?php if(!empty($item['inspection_id'])): ?> <a href="<?= APP_URL ?>/pm-records/<?= (int)$item['pm_record_id'] ?>/form">ดู IT-F-005</a><?php endif; ?><?php if(!empty($item['attachment_path'])): ?> <a href="<?= APP_URL ?>/pm-records/<?= (int)$item['pm_record_id'] ?>/attachment">PDF แนบ</a><?php endif; ?><div class="text-muted small"><?= nl2br($e($item['notes']??'')) ?></div></div><?php endforeach; ?></section><?php endif; ?>
</div>
<?php if(!$readOnly): ?><script src="<?= APP_URL ?>/assets/js/pm-inspection.js" defer></script><?php endif; ?>
