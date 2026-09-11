<?php
// Partial นี้ถูก include ทั้งจากหน้าเต็ม (users/index.php) และจาก AJAX endpoint
// ตัวแปรที่ต้องมี: $users, $totalUsers, $currentPage, $totalPages, $sort, $dir, $search, $currentUser

$nextDir = $dir === 'asc' ? 'desc' : 'asc';

function userSortLink(string $column, string $label, string $sort, string $dir, string $nextDir, string $search): string
{
    $icon = '<i class="bi bi-arrow-down-up sort-icon-idle"></i>';
    if ($sort === $column) {
        $icon = $dir === 'asc'
            ? '<i class="bi bi-sort-up-alt sort-icon-active"></i>'
            : '<i class="bi bi-sort-down sort-icon-active"></i>';
    }
    // href ยังใช้เป็น fallback กรณี JS ไม่ทำงาน แต่ data-* attribute ไว้ให้ JS ดักจับแทน
    return '<a href="#" class="sort-link" data-sort="' . htmlspecialchars($column) . '" '
        . 'data-dir="' . ($sort === $column ? $nextDir : 'asc') . '">'
        . htmlspecialchars($label) . ' ' . $icon . '</a>';
}
?>

<div class="table-responsive">
    <table class="table table-hover align-middle mb-0 table-modern">
        <thead>
            <tr>
                <th><?= userSortLink('first_name', 'ชื่อ-นามสกุล', $sort, $dir, $nextDir, $search) ?></th>
                <th><?= userSortLink('username', 'ชื่อผู้ใช้', $sort, $dir, $nextDir, $search) ?></th>
                <th><?= userSortLink('email', 'อีเมล', $sort, $dir, $nextDir, $search) ?></th>
                <th><?= userSortLink('svp_department_name', 'แผนก', $sort, $dir, $nextDir, $search) ?></th>
                <th><?= userSortLink('position_name', 'ตำแหน่ง', $sort, $dir, $nextDir, $search) ?></th>
                <th><?= userSortLink('user_role', 'สิทธิ์', $sort, $dir, $nextDir, $search) ?></th>
                <th><?= userSortLink('is_active', 'สถานะ', $sort, $dir, $nextDir, $search) ?></th>
                <?php if ($currentUser['user_role'] === 'admin'): ?><th class="text-end">จัดการ</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="user-avatar-sm">
                                <?= strtoupper(mb_substr($u['first_name'], 0, 1)) ?>
                            </span>
                            <span class="fw-medium"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></span>
                        </div>
                    </td>
                    <td class="text-muted"><?= htmlspecialchars($u['username']) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['svp_department_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($u['position_name'] ?? '-') ?></td>
                    <td>
                        <span class="badge-modern badge-<?= $u['user_role'] ?>">
                            <?= htmlspecialchars($u['user_role']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="status-dot status-active"></span><span class="text-success small fw-medium">ใช้งาน</span>
                        <?php else: ?>
                            <span class="status-dot status-inactive"></span><span class="text-muted small">ปิดการใช้งาน</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($currentUser['user_role'] === 'admin'): ?>
                        <td class="text-end">
                            <a href="<?= APP_URL ?>/users/<?= $u['svp_user_id'] ?>/edit" class="btn-icon" title="แก้ไข">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="<?= APP_URL ?>/users/<?= $u['svp_user_id'] ?>/delete" method="POST" class="d-inline js-delete-form"
                                onsubmit="return confirm('ยืนยันการลบผู้ใช้นี้?');">
                                <button type="submit" class="btn-icon btn-icon-danger" title="ลบ">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                        <?= $search !== '' ? 'ไม่พบผู้ใช้ที่ตรงกับ "' . htmlspecialchars($search) . '"' : 'ยังไม่มีข้อมูลผู้ใช้' ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <div class="pagination-modern">
        <span class="pagination-info">
            หน้า <?= $currentPage ?> จาก <?= $totalPages ?> (<?= number_format($totalUsers) ?> รายการ)
        </span>

        <div class="pagination-controls">
            <a href="#" class="page-btn js-page-link <?= $currentPage <= 1 ? 'disabled' : '' ?>" data-page="<?= max(1, $currentPage - 1) ?>">
                <i class="bi bi-chevron-left"></i>
            </a>

            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            ?>

            <?php if ($startPage > 1): ?>
                <a href="#" class="page-btn js-page-link" data-page="1">1</a>
                <?php if ($startPage > 2): ?><span class="page-dots">...</span><?php endif; ?>
            <?php endif; ?>

            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                <a href="#" class="page-btn js-page-link <?= $p === $currentPage ? 'active' : '' ?>" data-page="<?= $p ?>"><?= $p ?></a>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?><span class="page-dots">...</span><?php endif; ?>
                <a href="#" class="page-btn js-page-link" data-page="<?= $totalPages ?>"><?= $totalPages ?></a>
            <?php endif; ?>

            <a href="#" class="page-btn js-page-link <?= $currentPage >= $totalPages ? 'disabled' : '' ?>" data-page="<?= min($totalPages, $currentPage + 1) ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
    </div>
<?php endif; ?>