<?php
$selectedCategory=$data['category']??'machine';
if ($selectedCategory==='') $selectedCategory='machine';
$isMachine=$selectedCategory==='machine';
$currentUser=Session::get('user');
$department=isset($currentUser['svp_department_id'])?(new Department())->find((int)$currentUser['svp_department_id']):null;
?>
<h1 class="h3" id="repair-form-title"><?= $isMachine?'แจ้งซ่อมและตรวจรับเครื่องจักร':'แจ้งซ่อมใหม่' ?></h1>
<p class="text-muted" id="repair-form-description"><?= $isMachine?'MN-F-001-02 · กรอกส่วนแจ้งซ่อมก่อน ส่วนผลซ่อมและตรวจรับจะกรอกตามขั้นตอนงาน':'กรอกรายละเอียดปัญหาและแนบรูปภาพประกอบการแจ้งซ่อม' ?></p>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= Portal::esc($error) ?><?php if (!empty($_FILES['attachment']['name'])): ?> กรุณาเลือกรูปภาพแนบใหม่ก่อนส่ง<?php endif; ?></div><?php endif; ?>
<form method="post" action="<?= APP_URL ?>/tickets/create" enctype="multipart/form-data" class="card p-4" id="repair-request-form">
    <input type="hidden" name="_csrf" value="<?= Portal::esc(Session::csrfToken()) ?>">
    <div class="row g-3 mb-4">
        <div class="col-md-6"><label for="category" class="form-label">ประเภทงาน *</label><select id="category" name="category" class="form-select"><?php foreach (WorkItem::CATEGORIES['ticket'] as $key=>$label): ?><option value="<?= $key ?>" <?= $selectedCategory===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label for="work-title" class="form-label">หัวข้อแจ้งซ่อม *</label><input id="work-title" name="title" required maxlength="200" class="form-control" value="<?= Portal::esc($data['title']??'') ?>"></div>
    </div>
    <h2 class="h5">1. รายละเอียดการแจ้งซ่อม</h2>
    <div class="row g-4">
        <div class="col-lg-5">
            <label for="attachment" class="form-label">รูปภาพปัญหาหรือเครื่องจักร</label>
            <div class="border rounded bg-light p-3 text-center mb-2" style="min-height:200px">
                <p id="repair-photo-empty" class="text-muted mt-5">รูปภาพประกอบใบแจ้งซ่อม</p>
                <img id="repair-photo-preview" alt="ตัวอย่างรูปภาพที่เลือก" class="img-fluid rounded" style="max-height:360px" hidden>
            </div>
            <input id="attachment" type="file" name="attachment" accept="image/jpeg,image/png,image/webp" class="form-control" aria-describedby="repair-photo-help">
            <p id="repair-photo-help" class="form-text">JPG, PNG หรือ WebP จำนวน 1 รูป สูงสุด 10 MB</p>
        </div>
        <div class="col-lg-7">
            <fieldset id="machine-request-fields" <?= !$isMachine?'hidden disabled':'' ?>>
                <legend class="visually-hidden">ข้อมูลตามแบบ MN-F-001-02</legend>
                <div class="row g-3">
                    <div class="col-sm-6"><label for="report-date" class="form-label">วันที่แจ้งซ่อม *</label><input id="report-date" type="date" name="report_date" required max="<?= Portal::today() ?>" class="form-control" value="<?= Portal::esc($data['report_date']??Portal::today()) ?>"></div>
                    <div class="col-sm-6"><label for="report-time" class="form-label">เวลาแจ้งซ่อม *</label><input id="report-time" type="time" name="report_time" required class="form-control" value="<?= Portal::esc($data['report_time']??substr(Portal::now(),11,5)) ?>"></div>
                    <div class="col-12"><label for="machine-name" class="form-label">เครื่องจักร / รหัสเครื่องจักร *</label><input id="machine-name" name="machine_name" required maxlength="200" class="form-control" value="<?= Portal::esc($data['machine_name']??'') ?>"></div>
                    <div class="col-sm-6"><label for="department-name" class="form-label">แผนก *</label><input id="department-name" name="department_name" required maxlength="200" class="form-control" value="<?= Portal::esc($data['department_name']??($department['svp_department_name']??'')) ?>"></div>
                    <div class="col-sm-6"><label for="requester-name" class="form-label">ผู้แจ้งซ่อม</label><input id="requester-name" readonly class="form-control" value="<?= Portal::esc(trim(($currentUser['first_name']??'').' '.($currentUser['last_name']??''))) ?>"></div>
                    <div class="col-12"><label for="request-priority" class="form-label">ความเร่งด่วน *</label><select id="request-priority" name="request_priority" class="form-select" required><?php foreach (MachineRepairForm::PRIORITIES as $key=>$label): ?><option value="<?= $key ?>" <?= ($data['request_priority']??'P3')===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                    <div class="col-sm-6"><label for="access-date" class="form-label">วันที่สามารถเข้าแก้ไขได้</label><input id="access-date" name="access_date" type="date" class="form-control" value="<?= Portal::esc($data['access_date']??'') ?>"></div>
                    <div class="col-sm-6"><label for="access-time" class="form-label">เวลาที่สามารถเข้าแก้ไขได้</label><input id="access-time" name="access_time" type="time" class="form-control" value="<?= Portal::esc($data['access_time']??'') ?>"></div>
                </div>
            </fieldset>
            <fieldset id="generic-request-fields" <?= $isMachine?'hidden disabled':'' ?>>
                <legend class="visually-hidden">ข้อมูลงานซ่อมทั่วไป</legend>
                <label for="priority" class="form-label">ความเร่งด่วน</label><select id="priority" name="priority" class="form-select mb-3"><?php foreach (WorkItem::PRIORITIES as $key=>$label): ?><option value="<?= $key ?>" <?= ($data['priority']??'normal')===$key?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select>
                <label for="device-id" class="form-label">อุปกรณ์ IT ที่เกี่ยวข้อง</label><select id="device-id" name="device_id" class="form-select mb-3"><option value="">ไม่ระบุ</option><?php foreach ($devices as $device): ?><option value="<?= (int)$device['svp_device_id'] ?>" <?= ($data['device_id']??'')==$device['svp_device_id']?'selected':'' ?>><?= Portal::esc($device['svp_device_name']) ?></option><?php endforeach; ?></select>
                <label for="due-date" class="form-label">วันที่ต้องการ (ถ้ามี)</label><input id="due-date" name="due_date" type="date" min="<?= Portal::today() ?>" class="form-control" value="<?= Portal::esc($data['due_date']??'') ?>">
            </fieldset>
            <label for="location" class="form-label mt-3">สถานที่ / จุดติดตั้ง</label><input id="location" name="location" maxlength="200" class="form-control" value="<?= Portal::esc($data['location']??'') ?>">
        </div>
    </div>
    <label for="description" class="form-label mt-4">รายละเอียดปัญหาที่พบ *</label><textarea id="description" name="description" required maxlength="10000" rows="5" class="form-control mb-4"><?= Portal::esc($data['description']??'') ?></textarea>
    <div class="d-flex flex-wrap gap-2"><button class="btn btn-primary">ส่งแจ้งซ่อม</button><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/tickets">ยกเลิก</a></div>
</form>
<script src="<?= APP_URL ?>/assets/js/repair-form.js?v=<?= filemtime(BASE_PATH.'/public/assets/js/repair-form.js') ?>" defer></script>
