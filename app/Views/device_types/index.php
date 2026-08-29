<?php
$currentUser = Session::get('user');
$sort = $_GET['sort'] ?? 'device_type_id';
$dir  = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$nextDir = $dir === 'asc' ? 'desc' : 'asc';

function sortLink(string $column, string $label, string $sort, string $dir, string $nextDir): string
{
    $icon = '';
    if ($sort === $column) {
        $icon = $dir === 'asc' ? ' ▲' : ' ▼';
    }
    $url = APP_URL . '/device_types?sort=' . urlencode($column) . '&dir=' . ($sort === $column ? $nextDir : 'asc');
    return '<a href="' . $url . '" class="text-white text-decoration-none">' . htmlspecialchars($label) . $icon . '</a>';
}
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>จัดการประเภทอุปกรณ์</h1>
    <?php if ($currentUser['user_role'] === 'admin'): ?>
        <a href="<?= APP_URL ?>/device_types/create" class="btn btn-primary btn-sm">+ เพิ่มประเภทอุปกรณ์</a>
    <?php endif; ?>
</div>

<div class="table-responsive">
    <table class="table table-striped table-hover align-middle bg-white">
        <thead class="table-dark">
            <tr>
                <th style="cursor:pointer;"><?= sortLink('device_type_id', 'ID', $sort, $dir, $nextDir) ?></th>
                <th style="cursor:pointer;"><?= sortLink('device_type_name', 'ชื่อประเภทอุปกรณ์', $sort, $dir, $nextDir) ?></th>
                <?php if ($currentUser['user_role'] === 'admin'): ?><th class="text-end">จัดการ</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($devicetypes as $dt): ?>
                <tr>
                    <td><?= htmlspecialchars($dt['device_type_id'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($dt['device_type_name'] ?? '-') ?></td>
                    <?php if ($currentUser['user_role'] === 'admin'): ?>
                        <td class="text-end">
                            <a href="<?= APP_URL ?>/device_types/<?= $dt['device_type_id'] ?>/edit" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                            <form action="<?= APP_URL ?>/device_types/<?= $dt['device_type_id'] ?>/delete" method="POST" class="d-inline"
                                onsubmit="return confirm('ยืนยันการลบประเภทอุปกรณ์นี้?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($devicetypes)): ?>
                <tr>
                    <td colspan="3" class="text-center text-muted py-4">ยังไม่มีข้อมูลประเภทอุปกรณ์</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>