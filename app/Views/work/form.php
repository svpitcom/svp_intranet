<?php $path = $kind === 'ticket' ? '/tickets' : '/requests'; ?>
<h1 class="h3 mb-3"><?= $kind === 'ticket' ? 'แจ้งซ่อมใหม่' : 'ยื่นคำขออนุมัติ' ?></h1>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= Portal::esc($error) ?></div><?php endif; ?>
<form method="post" action="<?= APP_URL . $path ?>/create" enctype="multipart/form-data" class="card p-4" style="max-width:850px">
    <input type="hidden" name="_csrf" value="<?= Portal::esc(Session::csrfToken()) ?>">
    <label for="work-title" class="form-label">หัวข้อ *</label><input id="work-title" name="title" class="form-control mb-3" required maxlength="200" value="<?= Portal::esc($data['title'] ?? '') ?>">
    <div class="row">
        <div class="col-md-6 mb-3"><label for="category" class="form-label">ประเภท *</label><select id="category" name="category" class="form-select"><?php foreach (WorkItem::CATEGORIES[$kind] as $key => $label): ?><option value="<?= $key ?>" <?= ($data['category'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6 mb-3"><label for="priority" class="form-label">ความเร่งด่วน</label><select id="priority" name="priority" class="form-select"><?php foreach (WorkItem::PRIORITIES as $key => $label): ?><option value="<?= $key ?>" <?= ($data['priority'] ?? 'normal') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
    </div>
    <label for="description" class="form-label">รายละเอียด / เหตุผล *</label><textarea id="description" name="description" class="form-control mb-3" rows="6" required maxlength="10000"><?= Portal::esc($data['description'] ?? '') ?></textarea>
    <div class="row">
        <div class="col-md-6 mb-3"><label for="device-id" class="form-label">อุปกรณ์ที่เกี่ยวข้อง</label><select id="device-id" name="device_id" class="form-select">
                <option value="">ไม่ระบุ</option><?php foreach ($devices as $device): ?><option value="<?= (int)$device['svp_device_id'] ?>" <?= ($data['device_id'] ?? '') == $device['svp_device_id'] ? 'selected' : '' ?>><?= Portal::esc($device['svp_device_name']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-6 mb-3"><label for="location" class="form-label">สถานที่ / จุดติดตั้ง</label><input id="location" name="location" maxlength="200" class="form-control" value="<?= Portal::esc($data['location'] ?? '') ?>"></div>
    </div>
    <label for="due-date" class="form-label">วันที่ต้องการ (ถ้ามี)</label><input id="due-date" name="due_date" type="date" min="<?= Portal::today() ?>" class="form-control mb-3" value="<?= Portal::esc($data['due_date'] ?? '') ?>">
    <?php if ($kind === 'request'): ?><label for="approver" class="form-label">ผู้อนุมัติ *</label><select id="approver" name="approver_id" class="form-select mb-2" required>
            <option value="">เลือกผู้อนุมัติ</option><?php foreach ($people as $person): if ((int)$person['svp_user_id'] === (int)Session::get('user')['svp_user_id']) continue; ?><option value="<?= (int)$person['svp_user_id'] ?>" <?= ($data['approver_id'] ?? '') == $person['svp_user_id'] ? 'selected' : '' ?>><?= Portal::esc($person['first_name'] . ' ' . $person['last_name']) ?></option><?php endforeach; ?>
        </select>
        <p class="form-text">คำขอใช้ผู้อนุมัติหนึ่งคน ผู้อนุมัติต้องเป็นผู้จัดการหรือผู้ดูแล และเป็นคนละคนกับผู้ยื่น</p><?php endif; ?>
    <label for="attachment" class="form-label">เอกสารประกอบ PDF สูงสุด 10 MB</label><input id="attachment" type="file" name="attachment" accept="application/pdf" class="form-control mb-4">
    <div class="d-flex gap-2"><button class="btn btn-primary">ส่ง<?= $kind === 'ticket' ? 'แจ้งซ่อม' : 'คำขอ' ?></button><a class="btn btn-outline-secondary" href="<?= APP_URL . $path ?>">ยกเลิก</a></div>
</form>