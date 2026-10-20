<?php $pageUrl = static fn(int $p) => APP_URL . $listPath . '?' . http_build_query(($listQuery ?? []) + ['page' => $p]); ?>
<nav class="pagination-modern" aria-label="แบ่งหน้ารายการ">
    <span class="pagination-info"><?= $total ? 'แสดง ' . number_format($offset + 1) . '–' . number_format(min($offset + $size, $total)) . ' จาก ' . number_format($total) . ' รายการ' : 'ไม่มีรายการ' ?></span>
    <?php if ($pages > 1): ?><div class="pagination-controls">
            <?php if ($page > 1): ?><a class="page-btn" href="<?= Portal::esc($pageUrl($page - 1)) ?>" aria-label="หน้าก่อนหน้า">‹</a><?php endif; ?>
            <?php $numbers = array_unique(array_merge([1], range(max(1, $page - 2), min($pages, $page + 2)), [$pages]));
            $previous = 0;
            foreach ($numbers as $number): ?>
                <?php if ($previous && $number > $previous + 1): ?><span aria-hidden="true">…</span><?php endif; ?>
                <a class="page-btn <?= $number === $page ? 'active' : '' ?>" href="<?= Portal::esc($pageUrl($number)) ?>" <?= $number === $page ? 'aria-current="page"' : '' ?>><?= $number ?></a>
            <?php $previous = $number;
            endforeach; ?>
            <?php if ($page < $pages): ?><a class="page-btn" href="<?= Portal::esc($pageUrl($page + 1)) ?>" aria-label="หน้าถัดไป">›</a><?php endif; ?>
        </div><?php endif; ?>
</nav>