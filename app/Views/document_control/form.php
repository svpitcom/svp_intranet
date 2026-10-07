<?php $esc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div><span class="text-muted small">DOCUMENT CONTROL</span>
        <h1 class="h3 fw-bold mt-2"><?= $id ? 'รายละเอียดเอกสาร' : 'ลงทะเบียนเอกสาร' ?></h1>
    </div><a class="btn btn-outline-secondary align-self-start" href="<?= APP_URL ?>/document-control">กลับทะเบียน</a>
</div>
<?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= $esc($error) ?></div><?php endif; ?>
<form method="post" action="<?= APP_URL ?>/document-control/<?= $id ? (int)$id . '/edit' : 'create' ?>" class="card-modern p-4 mb-4">
    <input type="hidden" name="_csrf" value="<?= $esc(Session::csrfToken()) ?>"><input type="hidden" name="version" value="<?= (int)$document['version'] ?>"><input type="hidden" name="file_item_id" value="<?= $esc($document['file_item_id']) ?>">
    <h2 class="h5 mb-3">ข้อมูลทะเบียน</h2>
    <div class="row g-3">
        <?php foreach (['code' => ['รหัสเอกสาร', 80], 'title' => ['ชื่อเอกสาร', 255], 'revision' => ['Revision', 30]] as $key => [$label, $limit]): ?><div class="<?= $key === 'title' ? 'col-md-6' : 'col-md-3' ?>"><label class="form-label" for="doc-<?= $key ?>"><?= $label ?> *</label><input id="doc-<?= $key ?>" name="<?= $key ?>" class="form-control" maxlength="<?= $limit ?>" required value="<?= $esc($document[$key]) ?>"></div><?php endforeach; ?>
        <div class="col-md-6"><label class="form-label" for="doc-department">แผนกเจ้าของเอกสาร</label><select class="form-select" name="department_id" id="doc-department">
                <option value="">ส่วนกลาง / ไม่ระบุ</option><?php foreach ($departments as $department): ?><option value="<?= (int)$department['svp_department_id'] ?>" <?= (string)$document['department_id'] === (string)$department['svp_department_id'] ? 'selected' : '' ?>><?= $esc($department['svp_department_name']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-6"><label class="form-label" for="doc-status">สถานะ *</label><select name="status" id="doc-status" class="form-select"><?php foreach (ControlledDocument::STATUSES as $key => $label): ?><option value="<?= $key ?>" <?= $document['status'] === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label for="doc-effective" class="form-label">วันที่มีผล</label><input id="doc-effective" type="date" class="form-control" name="effective_date" value="<?= $esc($document['effective_date']) ?>"></div>
        <div class="col-md-6"><label for="doc-review" class="form-label">ทบทวนครั้งถัดไป</label><input id="doc-review" type="date" class="form-control" name="review_date" value="<?= $esc($document['review_date']) ?>"></div>
    </div>
    <div class="border rounded p-3 my-4">
        <h2 class="h6"><i class="bi bi-cloud-check me-2"></i>ไฟล์ต้นฉบับใน DCC</h2>
        <p class="text-muted mb-2"><?= $esc($document['file_name'] ?: ($document['file_item_id'] !== '' ? 'เลือกไฟล์แล้ว — ระบบจะตรวจสอบเมื่อบันทึก' : 'ยังไม่ได้เลือกไฟล์')) ?></p>
        <?php if (DocumentControlInput::safeUrl($document['file_url'])): ?><a class="btn btn-sm btn-outline-secondary" href="<?= $esc($document['file_url']) ?>" target="_blank" rel="noopener noreferrer">เปิดไฟล์</a><?php endif; ?>
        <a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/document-control/files?document_id=<?= (int)$id ?>">เลือก / เปลี่ยนไฟล์จาก DCC</a>
        <p class="small text-muted mt-2 mb-0">บันทึกข้อมูลทะเบียนก่อนเปลี่ยนไปหน้าเลือกไฟล์ เอกสารสถานะใช้งานต้องมีไฟล์และวันที่มีผล การเปลี่ยนไฟล์จะมีผลหลังบันทึกทะเบียน</p>
    </div>
    <label class="form-label" for="doc-notes">รายละเอียด / เหตุผลการเปลี่ยนแปลง</label><textarea id="doc-notes" name="notes" class="form-control mb-3" rows="3" maxlength="5000"><?= $esc($document['notes']) ?></textarea>
    <p class="small text-muted">สถานะทะเบียนกำหนดโดยผู้ดูแล การบันทึกนี้ไม่ได้ดำเนินขั้นตอนอนุมัติเอกสาร</p>
    <button class="btn-primary-modern" type="submit"><i class="bi bi-check2"></i> บันทึกทะเบียน</button>
</form>
<?php if ($id): ?><div class="card-modern">
        <div class="p-3 border-bottom">
            <h2 class="h5 mb-0">ประวัติการแก้ไขทะเบียน</h2>
            <p class="small text-muted mt-1 mb-0">เก็บข้อมูลทุกครั้งที่บันทึก · เวอร์ชันเนื้อหาไฟล์ดูผ่าน SharePoint</p>
        </div>
        <?php foreach ($history as $event): $snapshot = json_decode($event['snapshot'], true) ?: []; ?>
            <details class="p-3 border-bottom">
                <summary><?= $esc($event['changed_at']) ?> · <?= $esc(trim(($event['first_name'] ?? '') . ' ' . ($event['last_name'] ?? '')) ?: 'ผู้ดูแล #' . $event['changed_by']) ?> · Rev. <?= $esc($snapshot['revision'] ?? '') ?> · <?= $esc(ControlledDocument::STATUSES[$snapshot['status'] ?? ''] ?? '') ?></summary>
                <dl class="row mt-3 mb-0"><?php foreach (['code' => 'รหัส', 'title' => 'ชื่อ', 'revision' => 'Revision', 'department_id' => 'รหัสแผนก', 'effective_date' => 'วันที่มีผล', 'review_date' => 'วันทบทวน', 'file_name' => 'ไฟล์', 'notes' => 'รายละเอียด'] as $key => $label): ?><dt class="col-sm-3"><?= $label ?></dt>
                        <dd class="col-sm-9" style="white-space:pre-wrap"><?= $esc($snapshot[$key] ?? '—') ?></dd><?php endforeach; ?>
                </dl>
            </details><?php endforeach; ?>
    </div><?php endif; ?>