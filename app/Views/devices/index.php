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
    $url = APP_URL . '/devices?sort=' . urlencode($column) . '&dir=' . ($sort === $column ? $nextDir : 'asc') . Search::suffix();
    return '<a href="' . $url . '" class="sort-link">' . htmlspecialchars($label) . ' ' . $icon . '</a>';
}

/** สร้าง URL หน้าอื่น โดยคง sort/dir เดิมไว้เสมอ */
function pageUrl(int $page, string $sort, string $dir): string
{
    return APP_URL . '/devices?sort=' . urlencode($sort) . '&dir=' . urlencode($dir) . '&page=' . $page . Search::suffix();
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

<?php $searchPath = '/devices'; $searchPlaceholder = 'ชื่ออุปกรณ์, Serial, ยี่ห้อ, แผนก หรือผู้ถือครอง'; $resultCount = $totalDevices; require BASE_PATH . '/app/Views/partials/search.php'; ?>
<div class="card-modern">
    <?php if ($currentUser['user_role'] === 'admin'): ?>
    <div class="p-3 border-bottom">
        <p class="small text-muted">ปลายทาง Excel: SharePoint / IT · ส่งออกและนำเข้าจากโฟลเดอร์ IT โดยอัตโนมัติ</p>
        <form method="post" action="<?= APP_URL ?>/devices/sync-excel" onsubmit="return confirm('อัปเดต Excel ในโฟลเดอร์ IT ด้วยข้อมูลทั้งหมดจาก Intranet? ข้อมูลที่แก้ไว้ในไฟล์นั้นจะถูกแทนที่');">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-outline-primary" type="submit">อัปเดต Excel ทดสอบบน SharePoint</button>
        </form>
        <form method="post" action="<?= APP_URL ?>/devices/import-excel" class="mt-2" onsubmit="return confirm('นำข้อมูลจาก Excel มาเพิ่มหรือแก้ไขทะเบียนใน Intranet ใช่ไหม? ถ้าใช้รหัสอุปกรณ์เดิม ระบบจะแก้รายการนั้น หากเว้นรหัส ระบบจะเพิ่มรายการใหม่');">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-outline-success" type="submit">นำเข้าการแก้ไขจาก Excel</button>
        </form>
        <p class="small text-muted mt-2 mb-0">ส่งออกจะอัปเดตไฟล์ DEMO ด้วยข้อมูลทั้งหมดจาก Intranet ส่วนการนำเข้าจะเพิ่มแถวที่เว้นรหัสอุปกรณ์ หรือแก้รายการเดิมตามรหัส ตรวจประเภท แผนก และชื่อผู้รับผิดชอบให้ตรงกับข้อมูลใน Intranet ก่อนนำเข้า ระบบไม่นำการลบแถวไปลบอุปกรณ์</p>
    </div>
    <?php endif; ?>
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
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
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
