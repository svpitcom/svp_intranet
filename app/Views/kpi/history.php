<h1 class="h3">ประวัติผลงาน KPI</h1><p><?= Portal::esc($row['title']) ?> · <?= Portal::esc($row['period']) ?></p>
<a class="btn btn-outline-primary mb-3" href="<?= APP_URL ?>/kpi?period=<?= rawurlencode($row['period']) ?>">กลับหน้า KPI</a>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>ครั้งที่</th><th>ผลจริง</th><th>หมายเหตุ</th><th>ผู้บันทึก</th><th>เวลา</th></tr></thead><tbody>
<?php foreach ($history as $h): ?><tr><td><?= (int)$h['version'] ?></td><td><?= Portal::esc($h['actual']) ?></td><td><?= nl2br(Portal::esc($h['note'])) ?></td><td><?= Portal::esc(($h['first_name']??'').' '.($h['last_name']??'')) ?></td><td><?= Portal::esc($h['created_at']) ?></td></tr><?php endforeach; ?>
<?php if (!$history): ?><tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีประวัติการบันทึกด้วยตนเอง</td></tr><?php endif; ?>
</tbody></table></div></div><p class="small text-muted mt-2">แสดงประวัติล่าสุดไม่เกิน 100 ครั้ง</p>
