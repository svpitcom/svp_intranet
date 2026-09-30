<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>ประวัติการทำ PM</h4>
    <a class="btn btn-outline-danger" href="<?= APP_URL ?>/pm-records/export?search=<?= rawurlencode(Search::term()) ?>">Export PDF</a>
</div>
<?php $searchPath = '/pm-records'; $searchPlaceholder = 'ชื่อแผน, อุปกรณ์, ผู้ทำ, วันที่ หรือผลตรวจเช็ค'; $resultCount = count($records); require BASE_PATH . '/app/Views/partials/search.php'; ?>
<div class="table-responsive">
<table class="table table-striped bg-white">
    <thead><tr><th>วันที่ทำ</th><th>อุปกรณ์ / แผน PM</th><th>ผู้ทำ</th><th>ผลตรวจเช็ค</th><th>หมายเหตุ</th><th>ไฟล์แนบ</th></tr></thead>
    <tbody>
    <?php foreach ($records as $record): ?>
        <tr>
            <td><?= htmlspecialchars($record['performed_date']) ?></td>
            <td><?= htmlspecialchars($record['svp_device_name'] . ' / ' . $record['pm_title']) ?></td>
            <td><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></td>
            <td><?= htmlspecialchars(['completed' => 'ปกติดี', 'partial' => 'ทำได้บางส่วน', 'issue_found' => 'พบปัญหา'][$record['result_status']] ?? '-') ?></td>
            <td><?= nl2br(htmlspecialchars($record['notes'] ?? '')) ?></td>
            <td><?php if (!empty($record['attachment_path'])): ?>
                <a href="<?= APP_URL ?>/pm-records/<?= (int) $record['pm_record_id'] ?>/attachment"><?= htmlspecialchars($record['attachment_original_name'] ?? 'PDF') ?></a>
            <?php else: ?>—<?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$records): ?><tr><td colspan="6" class="text-center">ยังไม่มีประวัติการทำ PM</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
