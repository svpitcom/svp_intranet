<h4 class="mb-1">บันทึกการทำ PM: <?= htmlspecialchars($schedule['pm_title']) ?></h4>
<p class="text-muted">อุปกรณ์: <?= htmlspecialchars($schedule['svp_device_name']) ?></p>

<div class="row">
    <div class="col-md-6">
        <form method="POST" action="<?= APP_URL ?>/pm-schedules/<?= $schedule['pm_schedule_id'] ?>/record" class="card p-4">

            <div class="mb-3">
                <label class="form-label">วันที่ทำ</label>
                <input type="date" name="performed_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">ผลการตรวจเช็ค</label>
                <select name="result_status" class="form-select" required>
                    <option value="completed">ทำครบตามรายการ / ปกติดี</option>
                    <option value="partial">ทำได้บางส่วน</option>
                    <option value="issue_found">พบปัญหา ต้องแจ้งซ่อมเพิ่มเติม</option>
                </select>
            </div>

            <?php if (!empty($schedule['checklist'])): ?>
                <div class="mb-3">
                    <label class="form-label">รายการตรวจเช็คอ้างอิง</label>
                    <div class="border rounded p-2 bg-light" style="white-space: pre-line;">
                        <?= htmlspecialchars($schedule['checklist']) ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">หมายเหตุ</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="รายละเอียดเพิ่มเติม/ปัญหาที่พบ"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">แนบไฟล์รายงาน (PDF)</label>
                <input type="file" name="pm_attachment" class="form-control" accept="application/pdf">
                <div class="form-text">รองรับเฉพาะไฟล์ PDF ขนาดไม่เกิน 10 MB</div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">บันทึกว่าทำเสร็จแล้ว</button>
                <a href="<?= APP_URL ?>/pm-schedules" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>

    <div class="col-md-6">
        <h6>ประวัติการทำ PM ย้อนหลัง</h6>
        <ul class="list-group">
            <?php foreach ($history as $h): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <strong><?= htmlspecialchars($h['performed_date']) ?></strong>
                        <span class="badge bg-<?= $h['result_status'] === 'issue_found' ? 'danger' : ($h['result_status'] === 'partial' ? 'warning text-dark' : 'success') ?>">
                            <?= ['completed' => 'ปกติดี', 'partial' => 'ทำได้บางส่วน', 'issue_found' => 'พบปัญหา'][$h['result_status']] ?>
                        </span>
                    </div>
                    <small class="text-muted">โดย <?= htmlspecialchars($h['first_name'] . ' ' . $h['last_name']) ?></small>
                    <?php if ($h['notes']): ?><p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($h['notes'])) ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if (empty($history)): ?>
                <li class="list-group-item text-muted text-center">ยังไม่มีประวัติการทำ PM</li>
            <?php endif; ?>
        </ul>
        <ul class="list-group">
            <?php foreach ($history as $h): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <strong><?= htmlspecialchars($h['performed_date']) ?></strong>
                        <span class="badge bg-<?= $h['result_status'] === 'issue_found' ? 'danger' : ($h['result_status'] === 'partial' ? 'warning text-dark' : 'success') ?>">
                            <?= ['completed' => 'ปกติดี', 'partial' => 'ทำได้บางส่วน', 'issue_found' => 'พบปัญหา'][$h['result_status']] ?>
                        </span>
                    </div>
                    <small class="text-muted">โดย <?= htmlspecialchars($h['first_name'] . ' ' . $h['last_name']) ?></small>
                    <?php if ($h['notes']): ?><p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($h['notes'])) ?></p><?php endif; ?>

                    <?php if (!empty($h['attachment_path'])): ?>
                        <div class="mt-2">
                            <a href="<?= APP_URL ?>/uploads/pm_records/<?= htmlspecialchars($h['attachment_path']) ?>"
                                target="_blank" class="btn btn-sm btn-outline-secondary">
                                📄 <?= htmlspecialchars($h['attachment_original_name']) ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            <?php if (empty($history)): ?>
                <li class="list-group-item text-muted text-center">ยังไม่มีประวัติการทำ PM</li>
            <?php endif; ?>
        </ul>
    </div>
</div>