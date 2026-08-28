<?php $currentUser = Session::get('user'); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">จัดการผู้ใช้งานอิอิ</h4>
    <?php if ($currentUser['user_role'] === 'admin'): ?>
        <a href="<?= APP_URL ?>/users/create" class="btn btn-primary btn-sm">+ เพิ่มผู้ใช้</a>
    <?php endif; ?>
</div>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle bg-white">
        <thead class="table-dark">
            <tr>
                <th>ชื่อ-นามสกุล</th>
                <th>ชื่อผู้ใช้</th>
                <th>อีเมล</th>
                <th>แผนก</th>
                <th>ตำแหน่ง</th>
                <th>สิทธิ์</th>
                <th>สถานะ</th>
                <?php if ($currentUser['user_role'] === 'admin'): ?><th class="text-end">จัดการ</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['svp_department_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($u['position_name'] ?? '-') ?></td>
                    <td>
                        <span class="badge bg-<?= $u['user_role'] === 'admin' ? 'danger' : ($u['user_role'] === 'manager' ? 'warning text-dark' : 'secondary') ?>">
                            <?= htmlspecialchars($u['user_role']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['is_active']): ?><span class="badge bg-success">ใช้งาน</span>
                        <?php else: ?><span class="badge bg-secondary">ปิดการใช้งาน</span><?php endif; ?>
                    </td>
                    <?php if ($currentUser['user_role'] === 'admin'): ?>
                        <td class="text-end">
                            <a href="<?= APP_URL ?>/users/<?= $u['svp_user_id'] ?>/edit" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                            <form action="<?= APP_URL ?>/users/<?= $u['svp_user_id'] ?>/delete" method="POST" class="d-inline"
                                onsubmit="return confirm('ยืนยันการลบผู้ใช้นี้?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">ยังไม่มีข้อมูลผู้ใช้</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>