<?php
$currentUser = Session::get('user');
$planYear = (int) date('Y');
$pmView = ($_GET['view'] ?? '') === 'calendar' ? 'calendar' : 'table';
$calendar = PmCalendar::build($schedules, $_GET['month'] ?? null);
$searchExtra = ['view'=>$pmView, 'month'=>$calendar['month']];
$tableSchedules = $tableSchedules ?? $schedules;
$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$perPage = $perPage ?? 10;
$pageUrl = static fn(int $page): string => PmCalendar::url('table', $calendar['month'], Search::term()) . '&page=' . $page;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">แผนการบำรุงรักษาเชิงป้องกัน (PM)</h4>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/pm-schedules/export?search=<?= rawurlencode(Search::term()) ?>" class="btn btn-outline-danger btn-sm">📄 Export PDF</a>
        <?php if (in_array($currentUser['user_role'], ['admin', 'manager'], true)): ?>
            <a href="<?= APP_URL ?>/pm-schedules/create" class="btn btn-primary btn-sm">+ เพิ่มแผน PM</a>
        <?php endif; ?>
    </div>
</div>

<?php $searchPath = '/pm-schedules'; $searchPlaceholder = 'ชื่อแผน, อุปกรณ์, ผู้รับผิดชอบ, วันที่ หรือสถานะ'; $resultCount = count($schedules); require BASE_PATH . '/app/Views/partials/search.php'; ?>
<nav class="pm-view-switch" aria-label="มุมมองแผนบำรุงรักษา">
    <a class="<?= $pmView === 'table' ? 'active' : '' ?>" <?= $pmView === 'table' ? 'aria-current="page"' : '' ?> href="<?= htmlspecialchars(PmCalendar::url('table', $calendar['month'], Search::term())) ?>">ตารางทั้งหมด</a>
    <a class="<?= $pmView === 'calendar' ? 'active' : '' ?>" <?= $pmView === 'calendar' ? 'aria-current="page"' : '' ?> href="<?= htmlspecialchars(PmCalendar::url('calendar', $calendar['month'], Search::term())) ?>">ปฏิทิน</a>
</nav>
<?php if ($pmView === 'calendar'): ?>
    <?php require __DIR__ . '/_calendar.php'; ?>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle bg-white">
        <thead class="table-dark">
            <tr>
                <th>อุปกรณ์</th>
                <th>ชื่อแผน PM</th>
                <th>รอบ (วัน)</th>
                <th>ผู้รับผิดชอบ</th>
                <th>แผนปี <?= $planYear ?></th>
                <th>แผนปี <?= $planYear + 1 ?></th>
                <th>สถานะ</th>
                <th class="text-end">จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tableSchedules as $s): ?>
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
                    <td>
                        <?= htmlspecialchars($s['plan_current_year'] ?? '-') ?>
                        <?php if (!empty($s['plan_current_done'])): ?><span class="badge bg-success ms-1">ทำแล้ว</span><?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($s['plan_next_year'] ?? '-') ?>
                        <?php if (!empty($s['plan_next_projected'])): ?><small class="text-muted d-block">คาดการณ์ตามรอบ PM</small><?php endif; ?>
                    </td>
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
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($schedules)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">ยังไม่มีแผน PM</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<nav class="pagination-modern" aria-label="หน้าตารางแผน PM">
    <span class="pagination-info">
        <?php if (count($schedules)): ?>
            แสดง <?= number_format(($currentPage - 1) * $perPage + 1) ?>–<?= number_format(($currentPage - 1) * $perPage + count($tableSchedules)) ?> จาก <?= number_format(count($schedules)) ?> รายการ
        <?php else: ?>
            ไม่มีรายการ
        <?php endif; ?>
    </span>
    <?php if ($totalPages > 1): ?>
        <div class="pagination-controls">
            <?php if ($currentPage > 1): ?>
                <a class="page-btn" href="<?= htmlspecialchars($pageUrl($currentPage - 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="หน้าก่อนหน้า">‹</a>
            <?php else: ?>
                <span class="page-btn disabled" aria-hidden="true">‹</span>
            <?php endif; ?>
            <?php $startPage = max(1, $currentPage - 2); $endPage = min($totalPages, $currentPage + 2); ?>
            <?php if ($startPage > 1): ?>
                <a class="page-btn" href="<?= htmlspecialchars($pageUrl(1), ENT_QUOTES, 'UTF-8') ?>">1</a>
                <?php if ($startPage > 2): ?><span class="page-dots" aria-hidden="true">…</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($page = $startPage; $page <= $endPage; $page++): ?>
                <a class="page-btn <?= $page === $currentPage ? 'active' : '' ?>" href="<?= htmlspecialchars($pageUrl($page), ENT_QUOTES, 'UTF-8') ?>" <?= $page === $currentPage ? 'aria-current="page"' : '' ?>><?= $page ?></a>
            <?php endfor; ?>
            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?><span class="page-dots" aria-hidden="true">…</span><?php endif; ?>
                <a class="page-btn" href="<?= htmlspecialchars($pageUrl($totalPages), ENT_QUOTES, 'UTF-8') ?>"><?= $totalPages ?></a>
            <?php endif; ?>
            <?php if ($currentPage < $totalPages): ?>
                <a class="page-btn" href="<?= htmlspecialchars($pageUrl($currentPage + 1), ENT_QUOTES, 'UTF-8') ?>" aria-label="หน้าถัดไป">›</a>
            <?php else: ?>
                <span class="page-btn disabled" aria-hidden="true">›</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</nav>
<?php endif; ?>
