<?php $currentUser = Session::get('user'); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">จัดการแผนก</h4>
        <span class="text-muted small">ทั้งหมด <?= count($departments) ?> รายการ</span>
    </div>
    <?php if ($currentUser['user_role'] === 'admin'): ?>
        <a href="<?= APP_URL ?>/departments/create" class="btn-primary-modern btn-sm">
            <i class="bi bi-plus-lg"></i> เพิ่มแผนก
        </a>
    <?php endif; ?>
</div>

<?php $searchPath = '/departments'; $searchPlaceholder = 'รหัสแผนก หรือชื่อแผนก'; $resultCount = count($departments); require BASE_PATH . '/app/Views/partials/search.php'; ?>
<div class="card-modern">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-modern">
            <thead>
                <tr>
                    <th>รหัสแผนก</th>
                    <th>ชื่อแผนก</th>
                    <?php if ($currentUser['user_role'] === 'admin'): ?><th class="text-end">จัดการ</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $d): ?>
                    <tr>
                        <td>
                            <span class="code-chip"><?= htmlspecialchars($d['svp_code_department'] ?? '-') ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="dept-icon"><i class="bi bi-diagram-3-fill"></i></span>
                                <span class="fw-medium"><?= htmlspecialchars($d['svp_department_name']) ?></span>
                            </div>
                        </td>
                        <?php if ($currentUser['user_role'] === 'admin'): ?>
                            <td class="text-end">
                                <a href="<?= APP_URL ?>/departments/<?= $d['svp_department_id'] ?>/edit" class="btn-icon" title="แก้ไข">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="<?= APP_URL ?>/departments/<?= $d['svp_department_id'] ?>/delete" method="POST" class="d-inline"
                                    onsubmit="return confirm('ยืนยันการลบแผนกนี้?');">
                                    <button type="submit" class="btn-icon btn-icon-danger" title="ลบ">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($departments)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                            ยังไม่มีข้อมูลแผนก
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
