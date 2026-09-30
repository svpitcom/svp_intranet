<?php
$currentUser = Session::get('user');
$sort = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : 'svp_position_id';
$dir  = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$nextDir = $dir === 'asc' ? 'desc' : 'asc';

// helper: สร้างลิงก์หัวคอลัมน์ + ลูกศรบอกทิศทางการเรียง
function sortLink(string $column, string $label, string $sort, string $dir, string $nextDir): string
{
    $icon = '';
    if ($sort === $column) {
        $icon = $dir === 'asc' ? ' ▲' : ' ▼';
    }
    $url = APP_URL . '/positions?sort=' . urlencode($column) . '&dir=' . ($sort === $column ? $nextDir : 'asc') . Search::suffix();
    return '<a href="' . $url . '" class="text-white text-decoration-none">' . htmlspecialchars($label) . $icon . '</a>';
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>จัดการตำแหน่ง</h1>
    <?php if ($currentUser['user_role'] === 'admin'): ?>
        <a href="<?= APP_URL ?>/positions/create" class="btn btn-primary btn-sm">+ เพิ่มตำแหน่ง</a>
    <?php endif; ?>
</div>

<?php $searchPath = '/positions'; $searchPlaceholder = 'รหัสตำแหน่ง หรือชื่อตำแหน่ง'; $resultCount = count($positions); require BASE_PATH . '/app/Views/partials/search.php'; ?>
<div class="table-responsive">
    <table class="table table-striped table-hover align-middle bg-white">
        <thead class="table-dark">
            <tr>
                <th style="cursor:pointer;"><?= sortLink('svp_position_id', 'ID', $sort, $dir, $nextDir) ?></th>
                <th style="cursor:pointer;"><?= sortLink('position_name', 'ชื่อตำแหน่ง', $sort, $dir, $nextDir) ?></th>
                <?php if ($currentUser['user_role'] === 'admin'): ?><th class="text-end">จัดการ</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($positions as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['svp_position_id'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($p['position_name'] ?? '-') ?></td>
                    <?php if ($currentUser['user_role'] === 'admin'): ?>
                        <td class="text-end">
                            <a href="<?= APP_URL ?>/positions/<?= $p['svp_position_id'] ?>/edit" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                            <form action="<?= APP_URL ?>/positions/<?= $p['svp_position_id'] ?>/delete" method="POST" class="d-inline"
                                onsubmit="return confirm('ยืนยันการลบตำแหน่งนี้?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($positions)): ?>
                <tr>
                    <td colspan="3" class="text-center text-muted py-4">ยังไม่มีข้อมูลตำแหน่ง</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
