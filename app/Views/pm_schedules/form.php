<?php $isEdit = $schedule !== null; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><?= $isEdit ? 'แก้ไขแผน PM' : 'สร้างแผน PM ใหม่' ?></h4>
    <?php if ($isEdit): ?>
        <a href="<?= APP_URL ?>/pm-schedules/<?= $schedule['pm_schedule_id'] ?>/export"
            class="btn btn-outline-danger btn-sm" target="_blank">
            📄 Export แบบฟอร์ม PDF
        </a>
    <?php endif; ?>
</div>

<form method="POST"
    action="<?= APP_URL ?><?= $isEdit ? '/pm-schedules/' . $schedule['pm_schedule_id'] . '/edit' : '/pm-schedules/create' ?>"
    class="card p-4" style="max-width: 640px;">

    <div class="mb-3">
        <label class="form-label">อุปกรณ์</label>
        <select name="svp_device_id" class="form-select" required>
            <option value="">-- เลือกอุปกรณ์ --</option>
            <?php foreach ($devices as $d): ?>
                <option value="<?= $d['svp_device_id'] ?>"
                    <?= (($schedule['svp_device_id'] ?? null) == $d['svp_device_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['svp_device_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">ชื่อแผน PM</label>
        <input type="text" name="pm_title" class="form-control"
            value="<?= htmlspecialchars($schedule['pm_title'] ?? '') ?>"
            placeholder="เช่น ตรวจเช็คเครื่องอัดลมประจำเดือน" required>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">รอบความถี่ (วัน)</label>
            <input type="number" name="frequency_days" min="1" class="form-control"
                value="<?= htmlspecialchars($schedule['frequency_days'] ?? '30') ?>" required>
            <div class="form-text">เช่น 7 = รายสัปดาห์, 30 = รายเดือน, 90 = รายไตรมาส</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">ผู้รับผิดชอบ</label>
            <select name="responsible_user_id" class="form-select">
                <option value="">-- ไม่ระบุ --</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['svp_user_id'] ?>"
                        <?= (($schedule['responsible_user_id'] ?? null) == $u['svp_user_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if (!$isEdit): ?>
        <div class="mb-3">
            <label class="form-label">เริ่มนับรอบจากวันที่</label>
            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
            <div class="form-text">ระบบจะคำนวณวันครบกำหนดครั้งแรก = วันที่นี้ + รอบความถี่</div>
        </div>
    <?php else: ?>
        <div class="mb-3">
            <label class="form-label">วันครบกำหนดครั้งถัดไป</label>
            <input type="date" name="next_pm_date" class="form-control"
                value="<?= htmlspecialchars($schedule['next_pm_date'] ?? '') ?>" required>
        </div>
    <?php endif; ?>

    <div class="mb-3">
        <label class="form-label">รายการตรวจเช็ค (Checklist)</label>
        <textarea name="checklist" class="form-control" rows="4"
            placeholder="พิมพ์แต่ละรายการขึ้นบรรทัดใหม่ เช่น&#10;- เช็คระดับน้ำมัน&#10;- ทำความสะอาดไส้กรอง"><?= htmlspecialchars($schedule['checklist'] ?? '') ?></textarea>
    </div>

    <?php if ($isEdit): ?>
        <div class="mb-3 form-check">
            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                <?= !empty($schedule['is_active']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_active">เปิดใช้งานแผนนี้</label>
        </div>
    <?php endif; ?>

    <div class="mb-3">
        <label class="form-label">ไฟล์อ้างอิง (คู่มือ/มาตรฐานการตรวจเช็ค - PDF)</label>
        <input type="file" name="reference_doc" class="form-control" accept="application/pdf">
        <div class="form-text">ไม่เกิน 10 MB — เว้นว่างไว้ได้หากไม่มีไฟล์แนบ</div>

        <?php if ($isEdit && !empty($schedule['reference_doc_path'])): ?>
            <div class="mt-2">
                ไฟล์ปัจจุบัน:
                <a href="<?= APP_URL ?>/uploads/pm_schedules/<?= htmlspecialchars($schedule['reference_doc_path']) ?>" target="_blank">
                    📄 <?= htmlspecialchars($schedule['reference_doc_original_name']) ?>
                </a>
                <div class="form-text">แนบไฟล์ใหม่ด้านบนเพื่อแทนที่ไฟล์นี้</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'บันทึกการแก้ไข' : 'บันทึก' ?></button>
        <a href="<?= APP_URL ?>/pm-schedules" class="btn btn-outline-secondary">ยกเลิก</a>
    </div>
</form>