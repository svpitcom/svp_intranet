<?php $e=static fn($v)=>nl2br(Portal::esc((string)$v)); ?>
<!doctype html><html lang="th"><head><meta charset="UTF-8"><style>
<?php require __DIR__.'/_fonts.php'; ?>
@page { margin: 18px 22px; }
body { font-family:Sarabun,sans-serif; font-size:10px; line-height:1.2; color:#111; }
table { width:100%; border-collapse:collapse; table-layout:fixed; } th,td { border:1px solid #555; padding:2px 3px; vertical-align:top; overflow-wrap:break-word; } th { background:#e8e8e8; } .center { text-align:center; } .heading { font-size:16px; font-weight:bold; } .label { font-weight:bold; background:#eee; } .wrap { white-space:pre-wrap; } .footer { font-size:9px; text-align:right; margin-top:4px; } tr { page-break-inside:avoid; }
</style></head><body>
<div style="position:fixed;bottom:-10px;right:0;font-size:8px">MR-F-027-01 · KPI &amp; Action plan</div>
<table><tr><td colspan="4" class="heading">KPI and action plan</td><td>Year: <?= (int)$data['year'] ?></td><td colspan="3"><?= $e($data['title']) ?></td></tr>
<tr><td colspan="2">แผนก/ฝ่าย: <?= $e($data['department']) ?><br>ผู้รับผิดชอบ: <?= $e($data['owner']) ?></td><td colspan="2">วัตถุประสงค์ (objective)<br><?= $data['qms']?'[X]':'[ ]' ?> Quality (QMS)<br><?= $data['ems']?'[X]':'[ ]' ?> Environment (EMS)</td><td>Effective date<br><?= $e($data['effective_date']) ?></td><td colspan="2">Prepared by<br><?= $e($data['prepared_by']) ?></td><td>Approved by<br><?= $e($data['approved_by']) ?></td></tr></table>
<table><colgroup><col style="width:19%"><col style="width:4%"><col style="width:29%"><col style="width:9%"><col style="width:9%"><?php for($m=1;$m<=12;$m++): ?><col style="width:2.5%"><?php endfor; ?></colgroup>
<thead><tr><th style="width:19%">Target / วิธีวัดผล</th><th style="width:4%">Items</th><th style="width:29%">Activity/action plan</th><th style="width:9%">ผู้รับผิดชอบ</th><th style="width:9%">ทรัพยากรที่ใช้</th><th colspan="12" style="width:30%">แผนงาน (เดือน)</th></tr><tr><th></th><th></th><th></th><th></th><th></th><?php for($m=1;$m<=12;$m++): ?><th class="center"><?= $m ?></th><?php endfor; ?></tr></thead><tbody>
<?php foreach ($data['activities'] as $i=>$a): ?><tr>
<td><?php if ($i===0): ?><b>Target</b><br><?= $e($data['target']) ?><?php elseif ($i===4): ?><b>วิธีวัดผล</b><br><?= $e($data['measurement']) ?><?php elseif ($i===9): ?><b>ความถี่</b><br><?= $e($data['frequency']) ?><?php endif; ?></td>
<td class="center"><?= $i+1 ?></td><td><?= $e($a['activity']) ?></td><td><?= $e($a['owner']) ?></td><td><?= $e($a['resources']) ?></td><?php for($m=1;$m<=12;$m++): ?><td class="center"><?= in_array($m,$a['months'],true)?'X':'' ?></td><?php endfor; ?></tr><?php endforeach; ?>
</tbody></table>
<table><colgroup><col style="width:54%"><col style="width:8%"><col style="width:10%"><col style="width:28%"></colgroup><tr><th style="width:54%">Action taken (กรณี Target ไม่เป็นไปตามเป้าหมาย)</th><th style="width:8%">month</th><th style="width:10%">result</th><th style="width:28%">monitoring</th></tr>
<tr><td><?= $e($data['action_taken']) ?></td><td colspan="2"><table><?php foreach($data['results'] as $i=>$r): ?><tr><td><?= date('M',mktime(0,0,0,$i+1,1,2026)) ?></td><td><?= $e($r['result']) ?></td></tr><?php endforeach; ?><tr><td>YTD</td><td><?= $e($data['ytd']) ?></td></tr></table></td><td><img alt="Monitoring" style="width:100%" src="data:image/svg+xml;base64,<?= base64_encode(KpiFormExport::graph($data)) ?>"></td></tr></table>
</body></html>
