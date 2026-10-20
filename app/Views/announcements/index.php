<div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
    <div>
        <h1 class="h3">ประกาศภายใน</h1>
        <p class="text-muted">ข่าวสารและประกาศสำหรับองค์กรหรือแผนกของคุณ</p>
    </div><?php if (Portal::manager(Session::get('user'))): ?><div><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/announcements?manage=<?= $manage ? '0' : '1' ?>"><?= $manage ? 'ประกาศที่เผยแพร่' : 'จัดการรวมฉบับร่าง' ?></a> <a class="btn btn-primary" href="<?= APP_URL ?>/announcements/create">+ เขียนประกาศ</a></div><?php endif; ?>
</div>
<form method="get" class="d-flex gap-2 mb-3"><input type="hidden" name="manage" value="<?= $manage ? '1' : '0' ?>"><label class="visually-hidden" for="announcement-search">ค้นหาประกาศ</label><input id="announcement-search" name="q" class="form-control" style="max-width:400px" value="<?= Portal::esc($q) ?>" placeholder="ค้นหาประกาศ"><button class="btn btn-primary">ค้นหา</button></form>
<?php if (!$rows): ?><div class="card p-5 text-center text-muted">ยังไม่มีประกาศที่แสดงได้</div><?php endif; ?>
<?php foreach ($rows as $row): ?><article class="card p-4 mb-3">
        <div class="d-flex flex-wrap gap-2 mb-2"><?php if ($row['pinned']): ?><span class="badge bg-warning text-dark">ปักหมุด</span><?php endif; ?><span class="badge bg-secondary"><?= Portal::esc(Announcement::STATUSES[$row['status']]) ?></span><?php if ($row['read_at']): ?><span class="badge bg-success">รับทราบแล้ว</span><?php endif; ?></div>
        <h2 class="h5"><a href="<?= APP_URL ?>/announcements/<?= (int)$row['id'] ?>"><?= Portal::esc($row['title']) ?></a></h2>
        <p class="text-muted mb-2"><?= Portal::esc(mb_substr($row['body'], 0, 220)) ?></p><small><?= Portal::esc($row['publish_date'] . ' · ' . ($row['svp_department_name'] ?? 'ทุกแผนก')) ?></small>
    </article><?php endforeach; ?>
<?php $listPath = '/announcements';
$listQuery = ['q' => $q, 'manage' => $manage ? '1' : '0'];
require BASE_PATH . '/app/Views/partials/portal_pagination.php'; ?>