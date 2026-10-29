<div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">ตรวจ Backup Server</h1>
        <p class="text-muted mb-0">IT-F-004 · แบบตรวจสอบการสำรองข้อมูลรายเดือน</p>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <section class="card h-100">
            <div class="card-body">
                <h2 class="h5">สร้างแบบฟอร์มรายเดือน</h2>
                <form action="<?= APP_URL ?>/it-backup/create" method="get" class="d-flex flex-wrap align-items-end gap-2"><label class="form-label mb-0">ประจำเดือน<input class="form-control" type="month" name="month" required min="2000-01" max="2099-12" value="<?= Portal::esc($month ?: substr(Portal::today(), 0, 7)) ?>"></label><button class="btn btn-primary">+ สร้างแบบฟอร์ม</button></form>
            </div>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="card h-100">
            <div class="card-body">
                <h2 class="h5">ค้นหาแบบฟอร์มที่บันทึกแล้ว</h2>
                <form method="get" class="d-flex flex-wrap align-items-end gap-2"><label class="form-label mb-0">เดือน<input class="form-control" type="month" name="month" min="2000-01" max="2099-12" value="<?= Portal::esc($month) ?>"></label><button class="btn btn-outline-primary">แสดงรายการ</button><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/it-backup">ทุกเดือน</a></form>
            </div>
        </section>
    </div>
</div>
<section class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-3">
            <h2 class="h5 mb-0">แบบฟอร์มที่บันทึกแล้ว</h2><span class="text-muted"><?= (int)$paging['total'] ?> รายการ</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th scope="col">เดือน</th>
                        <th scope="col">Location</th>
                        <th scope="col">Server</th>
                        <th scope="col">ฉบับ</th>
                        <th scope="col">แก้ไขล่าสุด</th>
                        <th scope="col">เปิดเอกสาร</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($forms as $f): ?><tr>
                            <td><?= Portal::esc($f['month']) ?></td>
                            <td><?= Portal::esc($f['location']) ?></td>
                            <td><?= Portal::esc($f['server_name']) ?></td>
                            <td><?= (int)$f['version'] ?></td>
                            <td><?= Portal::esc($f['updated_at']) ?></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/it-backup/<?= (int)$f['id'] ?>">เปิดแบบฟอร์ม</a></td>
                        </tr><?php endforeach; ?>
                    <?php if (!$forms): ?><tr>
                            <td colspan="6" class="py-4 text-center text-muted">ยังไม่มีแบบฟอร์ม<?= $month ? 'ในเดือนนี้' : '' ?> เลือกเดือนด้านบนเพื่อเริ่มกรอก</td>
                        </tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($paging['pages'] > 1): ?><nav class="d-flex gap-3 align-items-center" aria-label="หน้ารายการแบบฟอร์ม"><?php if ($paging['page'] > 1): ?><a href="?<?= Portal::esc(http_build_query(['month' => $month, 'page' => $paging['page'] - 1])) ?>">ก่อนหน้า</a><?php endif; ?><span>หน้า <?= $paging['page'] ?> / <?= $paging['pages'] ?></span><?php if ($paging['page'] < $paging['pages']): ?><a href="?<?= Portal::esc(http_build_query(['month' => $month, 'page' => $paging['page'] + 1])) ?>">ถัดไป</a><?php endif; ?></nav><?php endif; ?>
    </div>
</section>