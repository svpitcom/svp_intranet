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
    class="card p-4" style="max-width: 640px;" enctype="multipart/form-data">

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

    <!-- ===== Checklist แบบเพิ่มทีละข้อ ===== -->
    <div class="mb-3">
        <label class="form-label">รายการตรวจเช็ค (Checklist)</label>

        <?php
        // แปลงข้อความเดิม (คั่นด้วย \n) ให้กลายเป็น array สำหรับแสดงเป็นแถว
        $existingItems = array_values(array_filter(
            array_map('trim', explode("\n", $schedule['checklist'] ?? '')),
            fn($line) => $line !== ''
        ));
        if (empty($existingItems)) {
            $existingItems = ['']; // อย่างน้อยแสดง 1 ช่องว่างให้กรอก
        }
        ?>

        <div id="checklist-items">
            <?php foreach ($existingItems as $item): ?>
                <div class="input-group mb-2 checklist-row">
                    <span class="input-group-text">☑</span>
                    <input type="text" name="checklist[]" class="form-control" aria-label="รายการตรวจเช็ค"
                        value="<?= htmlspecialchars($item) ?>" placeholder="เช่น เช็คระดับน้ำมัน">
                    <button type="button" class="btn btn-outline-danger btn-remove-row" aria-label="ลบรายการตรวจเช็ค">✕</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" id="btn-add-checklist" class="btn btn-sm btn-outline-primary mt-1">
            + เพิ่มรายการ
        </button>
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
                <a href="<?= APP_URL ?>/pm-schedules/<?= (int) $schedule['pm_schedule_id'] ?>/attachment" target="_blank" rel="noopener">
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
<input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('checklist-items');
        const addBtn = document.getElementById('btn-add-checklist');

        function createRow(value = '') {
            const row = document.createElement('div');
            row.className = 'input-group mb-2 checklist-row';
            row.innerHTML = `
            <span class="input-group-text">☑</span>
            <input type="text" name="checklist[]" class="form-control" aria-label="รายการตรวจเช็ค" placeholder="เช่น เช็คระดับน้ำมัน">
            <button type="button" class="btn btn-outline-danger btn-remove-row" aria-label="ลบรายการตรวจเช็ค">✕</button>
        `;
            row.querySelector('input').value = value;
            return row;
        }

        addBtn.addEventListener('click', function() {
            const row = createRow();
            container.appendChild(row);
            row.querySelector('input').focus();
        });

        // ใช้ event delegation เพราะแถวถูกเพิ่มเข้ามาใหม่ทีหลัง
        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-remove-row')) {
                const rows = container.querySelectorAll('.checklist-row');
                // เหลืออย่างน้อย 1 แถวเสมอ กันฟอร์มไม่มีช่องให้กรอกเลย
                if (rows.length > 1) {
                    e.target.closest('.checklist-row').remove();
                } else {
                    e.target.closest('.checklist-row').querySelector('input').value = '';
                }
            }
        });
    });
</script>
