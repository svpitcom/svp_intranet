<div class="d-flex gap-2 justify-content-between flex-wrap mb-3"><h1 class="h3">KPI &amp; Action plan ของแผนก</h1><div><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/kpi?department_id=<?= (int)($departmentId??0) ?: '' ?>">KPI รายเดือน</a> <?php if (KpiPolicy::manages($user,(int)($user['svp_department_id']??0))): ?><a class="btn btn-primary" href="<?= APP_URL ?>/kpi/forms/create?department_id=<?= (int)($departmentId??0) ?: '' ?>">+ สร้างแบบ MR-F-027-01</a><?php endif; ?></div></div>
<form method="get" class="d-flex gap-2 align-items-end mb-4 flex-wrap">
    <?php require __DIR__.'/_department_filter.php'; ?>
    <button class="btn btn-primary">แสดงผล</button>
</form>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>ปี ค.ศ.</th><th>KPI</th><th>แผนก/ฝ่าย</th><th>แก้ไขล่าสุด</th><th></th></tr></thead><tbody>
<?php foreach ($forms as $f): ?><tr><td><?= (int)$f['year'] ?></td><td><?= Portal::esc($f['title']) ?></td><td><?= Portal::esc($f['svp_department_name']) ?></td><td><?= Portal::esc($f['updated_at']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/kpi/forms/<?= (int)$f['id'] ?>">เปิดแบบฟอร์ม</a></td></tr><?php endforeach; ?>
<?php if (!$forms): ?><tr><td colspan="5">ยังไม่มีแบบฟอร์มรายปี</td></tr><?php endif; ?>
</tbody></table></div>
