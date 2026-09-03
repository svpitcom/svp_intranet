<?php
$currentUser = Session::get('user');
$nextDir = $dir === 'asc' ? 'desc' : 'asc';

function sortLink(string $column, string $label, string $sort, string $dir, string $nextDir): string
{
    $icon = '<i class="bi bi-arrow-down-up sort-icon-idle"></i>';
    if ($sort === $column) {
        $icon = $dir === 'asc'
            ? '<i class="bi bi-sort-up-alt sort-icon-active"></i>'
            : '<i class="bi bi-sort-down sort-icon-active"></i>';
    }
    $url = APP_URL . '/devices?sort=' . urlencode($column) . '&dir=' . ($sort === $column ? $nextDir : 'asc');
    return '<a href="' . $url . '" class="sort-link">' . htmlspecialchars($label) . ' ' . $icon . '</a>';
}

/** สร้าง URL หน้าอื่น โดยคง sort/dir เดิมไว้เสมอ */
function pageUrl(int $page, string $sort, string $dir): string
{
    return APP_URL . '/devices?sort=' . urlencode($sort) . '&dir=' . urlencode($dir) . '&page=' . $page;
}
?>

<?php if (Session::get('success')): ?>
    <div class="alert-modern alert-success-modern">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars(Session::get('success')) ?>
    </div>
<?php endif; ?>
<?php if (Session::get('error')): ?>
    <div class="alert-modern alert-danger-modern">
        <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars(Session::get('error')) ?>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">จัดการอุปกรณ์</h4>
        <span class="text-muted small">ทั้งหมด <?= number_format($totalDevices) ?> รายการ</span>
    </div>
    <?php if ($currentUser['user_role'] === 'admin'): ?>
        <a href="<?= APP_URL ?>/devices/create" class="btn-primary-modern btn-sm">
            <i class="bi bi-plus-lg"></i> เพิ่มอุปกรณ์
        </a>
    <?php endif; ?>
</div>

<div class="card-modern">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 table-modern">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?= sortLink('svp_device_name', 'ชื่ออุปกรณ์', $sort, $dir, $nextDir) ?></th>
                    <th><?= sortLink('brand_name', 'ยี่ห้อ', $sort, $dir, $nextDir) ?></th>
                    <th>โมเดล</th>
                    <th>SerialNumber</th>
                    <th><?= sortLink('device_type_name', 'ประเภทอุปกรณ์', $sort, $dir, $nextDir) ?></th>
                    <th><?= sortLink('svp_department_name', 'แผนก', $sort, $dir, $nextDir) ?></th>
                    <th>พนักงานใช้งาน</th>
                    <th><?= sortLink('is_active', 'สถานะ', $sort, $dir, $nextDir) ?></th>
                    <?php if ($currentUser['user_role'] === 'admin'): ?><th class="text-end">จัดการ</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devices as $dv): ?>
                    <tr>
                        <td class="text-muted"><?= htmlspecialchars($dv['svp_device_id']) ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="device-icon"><i class="bi bi-laptop"></i></span>
                                <span class="fw-medium"><?= htmlspecialchars($dv['svp_device_name']) ?></span>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($dv['brand_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($dv['model_name'] ?? '-') ?></td>
                        <td><span class="code-chip"><?= htmlspecialchars($dv['serial_number'] ?? '-') ?></span></td>
                        <td><?= htmlspecialchars($dv['device_type_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($dv['svp_department_name'] ?? '-') ?></td>
                        <td class="text-muted"><?= htmlspecialchars($dv['first_name'] . ' ' . $dv['last_name']) ?></td>
                        <td>
                            <?php if ($dv['is_active']): ?>
                                <span class="status-dot status-active"></span><span class="text-success small fw-medium">ใช้งาน</span>
                            <?php else: ?>
                                <span class="status-dot status-inactive"></span><span class="text-muted small">ปิดการใช้งาน</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($currentUser['user_role'] === 'admin'): ?>
                            <td class="text-end">
                                <a href="<?= APP_URL ?>/devices/<?= $dv['svp_device_id'] ?>/edit" class="btn-icon" title="แก้ไข">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="<?= APP_URL ?>/devices/<?= $dv['svp_device_id'] ?>/delete" method="POST" class="d-inline"
                                    onsubmit="return confirm('ยืนยันการลบอุปกรณ์นี้?');">
                                    <button type="submit" class="btn-icon btn-icon-danger" title="ลบ">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($devices)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-3 d-block mb-2 opacity-50"></i>
                            ยังไม่มีข้อมูลอุปกรณ์
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination-modern">
            <span class="pagination-info">
                หน้า <?= $currentPage ?> จาก <?= $totalPages ?> (<?= number_format($totalDevices) ?> รายการ)
            </span>

            <div class="pagination-controls">
                <a href="<?= pageUrl(max(1, $currentPage - 1), $sort, $dir) ?>"
                    class="page-btn <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>

                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                ?>

                <?php if ($startPage > 1): ?>
                    <a href="<?= pageUrl(1, $sort, $dir) ?>" class="page-btn">1</a>
                    <?php if ($startPage > 2): ?><span class="page-dots">...</span><?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                    <a href="<?= pageUrl($p, $sort, $dir) ?>" class="page-btn <?= $p === $currentPage ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?><span class="page-dots">...</span><?php endif; ?>
                    <a href="<?= pageUrl($totalPages, $sort, $dir) ?>" class="page-btn"><?= $totalPages ?></a>
                <?php endif; ?>

                <a href="<?= pageUrl(min($totalPages, $currentPage + 1), $sort, $dir) ?>"
                    class="page-btn <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .btn-primary-modern {
        background: linear-gradient(135deg, #4f7cff, #2b6cb0);
        border: none;
        color: #fff;
        border-radius: 8px;
        padding: 0.4rem 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-primary-modern:hover {
        box-shadow: 0 4px 10px rgba(43, 108, 176, 0.3);
        color: #fff;
    }

    .card-modern {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #eef0f2;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        overflow: hidden;
    }

    .table-modern thead th {
        background: #f8f9fb;
        color: #6b7280;
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 1px solid #eef0f2;
        padding: 0.85rem 1rem;
        white-space: nowrap;
    }

    .table-modern tbody td {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        color: #374151;
        border-bottom: 1px solid #f4f5f7;
    }

    .table-modern tbody tr:last-child td {
        border-bottom: none;
    }

    .table-modern tbody tr:hover {
        background: #f8faff;
    }

    .sort-link {
        color: #6b7280;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: color 0.15s ease;
    }

    .sort-link:hover {
        color: #2b6cb0;
    }

    .sort-icon-idle {
        font-size: 0.7rem;
        opacity: 0.4;
    }

    .sort-icon-active {
        font-size: 0.75rem;
        color: #4f7cff;
    }

    .device-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #eef2ff;
        color: #4f7cff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .code-chip {
        display: inline-block;
        background: #f3f4f6;
        color: #4b5563;
        font-family: 'SFMono-Regular', Consolas, monospace;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
    }

    .status-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .status-active {
        background: #22c55e;
    }

    .status-inactive {
        background: #9ca3af;
    }

    .btn-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: #6b7280;
        transition: all 0.2s ease;
    }

    .btn-icon:hover {
        background: #eef2ff;
        color: #2b6cb0;
    }

    .btn-icon-danger:hover {
        background: #fee2e2;
        color: #dc2626;
    }

    .alert-modern {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        font-size: 0.88rem;
        margin-bottom: 1rem;
    }

    .alert-success-modern {
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    .alert-danger-modern {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    /* ---- Pagination ---- */
    .pagination-modern {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.9rem 1.2rem;
        border-top: 1px solid #f4f5f7;
        flex-wrap: wrap;
        gap: 0.6rem;
    }

    .pagination-info {
        font-size: 0.82rem;
        color: #9ca3af;
    }

    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        padding: 0 0.5rem;
        border-radius: 8px;
        color: #6b7280;
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 500;
        transition: all 0.15s ease;
    }

    .page-btn:hover {
        background: #eef2ff;
        color: #2b6cb0;
    }

    .page-btn.active {
        background: linear-gradient(135deg, #4f7cff, #2b6cb0);
        color: #fff;
    }

    .page-btn.disabled {
        opacity: 0.35;
        pointer-events: none;
    }

    .page-dots {
        color: #9ca3af;
        font-size: 0.82rem;
        padding: 0 0.2rem;
    }
</style>