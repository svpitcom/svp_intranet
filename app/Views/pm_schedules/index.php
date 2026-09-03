<?php $currentUser = Session::get('user'); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">แผนการบำรุงรักษาเชิงป้องกัน (PM)</h4>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/pm-schedules/export" class="btn btn-outline-danger btn-sm">📄 Export PDF</a>
        <?php if (in_array($currentUser['user_role'], ['admin', 'manager'], true)): ?>
            <a href="<?= APP_URL ?>/pm-schedules/create" class="btn btn-primary btn-sm">+ เพิ่มแผน PM</a>
        <?php endif; ?>
    </div>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle bg-white">
        <thead class="table-dark">
            <tr>
                <th>อุปกรณ์</th>
                <th>ชื่อแผน PM</th>
                <th>รอบ (วัน)</th>
                <th>ผู้รับผิดชอบ</th>
                <th>ครบกำหนดครั้งถัดไป</th>
                <th>สถานะ</th>
                <th class="text-end">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($schedules as $s): ?>
                <?php
                $badgeMap = [
                    'overdue' => ['bg-danger', 'เกินกำหนด'],
                    'soon'    => ['bg-warning text-dark', 'ใกล้ครบกำหนด'],
                    'normal'  => ['bg-success', 'ปกติ'],
                ];
                [$badgeClass, $badgeLabel] = $badgeMap[$s['pm_status']];
                ?>
                <tr>
                    <td><?= htmlspecialchars($s['svp_device_name']) ?></td>
                    <td><?= htmlspecialchars($s['pm_title']) ?></td>
                    <td>ทุก <?= (int) $s['frequency_days'] ?> วัน</td>
                    <td><?= htmlspecialchars(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?: '-') ?></td>
                    <td><?= htmlspecialchars($s['next_pm_date']) ?></td>
                    <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
                    <td class="text-end">
                        <a href="<?= APP_URL ?>/pm-schedules/<?= $s['pm_schedule_id'] ?>/record" class="btn btn-sm btn-outline-success">บันทึกว่าทำแล้ว</a>
                        <?php if (in_array($currentUser['user_role'], ['admin', 'manager'], true)): ?>
                            <a href="<?= APP_URL ?>/pm-schedules/<?= $s['pm_schedule_id'] ?>/edit" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                        <?php endif; ?>
                        <?php if ($currentUser['user_role'] === 'admin'): ?>
                            <form action="<?= APP_URL ?>/pm-schedules/<?= $s['pm_schedule_id'] ?>/delete" method="POST" class="d-inline"
                                onsubmit="return confirm('ยืนยันการลบแผน PM นี้?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($schedules)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">ยังไม่มีแผน PM</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>