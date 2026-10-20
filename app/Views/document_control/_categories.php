<details class="card-modern p-3 mb-3" open>
    <summary class="fw-bold">หมวดทะเบียนตามโฟลเดอร์</summary>
    <nav class="d-flex flex-wrap gap-2 mt-3" aria-label="หมวดทะเบียนเอกสาร">
        <?php foreach (['' => '0.รวมทะเบียนเอกสาร'] + DocumentCategory::LABELS + ['UNSORTED' => 'ยังไม่จัดหมวด'] as $key => $label): ?>
            <a class="btn btn-sm <?= $category === $key ? 'btn-primary' : 'btn-outline-primary' ?>" <?= $category === $key ? 'aria-current="page"' : '' ?> href="<?= APP_URL ?>/document-control?<?= $esc(http_build_query(['category' => $key, 'q' => $q, 'status' => $status])) ?>"><?= $esc($label) ?> <span class="ms-1">(<?= number_format($key === '' ? array_sum($categoryCounts) : ($categoryCounts[$key === 'UNSORTED' ? '' : $key] ?? 0)) ?>)</span></a>
        <?php endforeach; ?>
    </nav>
</details>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="h5 mb-0"><?= $esc(DocumentCategory::LABELS[$category] ?? ($category === 'UNSORTED' ? 'ยังไม่จัดหมวด' : 'รวมทะเบียนเอกสาร')) ?></h2>
    <?php if ($category !== 'UNSORTED'): ?><a class="btn btn-outline-primary" href="<?= APP_URL ?>/document-control/files?category=<?= $esc($category ?: 'MASTER') ?>"><i class="bi bi-folder2-open"></i> เปิดโฟลเดอร์หมวดนี้ใน SharePoint</a><?php endif; ?>
</div>