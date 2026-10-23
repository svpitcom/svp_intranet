<div class="d-flex gap-2 justify-content-between flex-wrap mb-3"><h1 class="h3">KPI &amp; Action plan รายปี</h1><div><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/kpi">KPI รายเดือน</a> <?php if (Portal::manager($user)): ?><a class="btn btn-primary" href="<?= APP_URL ?>/kpi/forms/create">+ สร้างแบบ MR-F-027-01</a><?php endif; ?></div></div>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>ปี ค.ศ.</th><th>KPI</th><th>แผนก/ฝ่าย</th><th>แก้ไขล่าสุด</th><th></th></tr></thead><tbody>
<?php foreach ($forms as $f): ?><tr><td><?= (int)$f['year'] ?></td><td><?= Portal::esc($f['title']) ?></td><td><?= Portal::esc($f['svp_department_name']) ?></td><td><?= Portal::esc($f['updated_at']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/kpi/forms/<?= (int)$f['id'] ?>">เปิดแบบฟอร์ม</a></td></tr><?php endforeach; ?>
<?php if (!$forms): ?><tr><td colspan="5">ยังไม่มีแบบฟอร์มรายปี</td></tr><?php endif; ?>
</tbody></table></div>
