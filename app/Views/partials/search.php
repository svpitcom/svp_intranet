<?php $searchTerm = Search::term(); ?>
<form method="GET" action="<?= APP_URL . $searchPath ?>" class="list-search" role="search">
    <label for="list-search-input" class="visually-hidden">ค้นหาข้อมูลในหน้านี้</label>
    <div class="search-input-wrap">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="list-search-input" name="search" class="search-input" value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($searchPlaceholder) ?>">
    </div>
    <?php foreach (['sort', 'dir'] as $searchKey): ?>
        <?php if (isset($_GET[$searchKey]) && is_string($_GET[$searchKey])): ?>
            <input type="hidden" name="<?= $searchKey ?>" value="<?= htmlspecialchars($_GET[$searchKey], ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary">ค้นหา</button>
    <?php foreach (($searchExtra ?? []) as $extraKey => $extraValue): ?>
        <input type="hidden" name="<?= htmlspecialchars($extraKey) ?>" value="<?= htmlspecialchars($extraValue) ?>">
    <?php endforeach; ?>
    <?php if ($searchTerm !== ''): ?><a class="btn btn-outline-secondary" href="<?= htmlspecialchars(APP_URL . $searchPath . (!empty($searchExtra) ? '?' . http_build_query($searchExtra) : '')) ?>">ล้างการค้นหา</a><?php endif; ?>
</form>
<?php if ($searchTerm !== ''): ?>
    <p class="search-summary" role="status"><?= $resultCount ? 'พบ ' . number_format($resultCount) . ' รายการ' : 'ไม่พบข้อมูล' ?> สำหรับ “<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>”</p>
<?php endif; ?>
