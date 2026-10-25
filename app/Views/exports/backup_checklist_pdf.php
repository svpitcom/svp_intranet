<?php $e=static fn($v)=>nl2br(Portal::esc((string)$v)); ?>
<!doctype html><html lang="th"><head><meta charset="UTF-8"><style>
<?php require __DIR__.'/_fonts.php'; ?>
@page { margin:24px; }
body { font-family:Sarabun,sans-serif; font-size:9px; color:#111; line-height:1.25; }
table { width:100%; border-collapse:collapse; table-layout:fixed; }
th,td { border:1px solid #333; padding:4px; vertical-align:middle; word-wrap:break-word; }
th { font-weight:bold; text-align:center; }
thead { display:table-header-group; }
tr { page-break-inside:avoid; }
.title { font-size:11px; text-align:center; }
.daily td { height:14px; }
.center { text-align:center; }
.footer { text-align:right; font-size:8px; margin-top:4px; }
.signatures { margin-top:0; page-break-inside:avoid; }
</style></head><body>
<table><tr><td style="width:24%">Location : <?= $e($data['location']) ?><br>ประจำเดือน <?= $e($data['month']) ?></td><td class="title"><?= BackupChecklist::TITLE ?><br><?= $e($data['server_name']) ?></td></tr></table>
<p class="center">FILE to cloud_HOUR · Incremental 1 (Daily)</p>
<table class="daily"><colgroup><col style="width:14%"><col style="width:10%"><col style="width:10%"><col style="width:10%"><col style="width:10%"><col style="width:10%"><col style="width:16%"><col style="width:20%"></colgroup><thead><tr><th style="width:14%">Date</th><th style="width:10%">Data</th><th style="width:10%">Data Full</th><th style="width:10%">Start</th><th style="width:10%">End</th><th style="width:10%">Status</th><th style="width:16%">ผู้ตรวจเช็ค</th><th style="width:20%">หมายเหตุ</th></tr></thead><tbody>
<?php for($day=1;$day<=31;$day++): $entry=$data['days'][$day]??null; ?><tr><td class="center"><?= $entry?sprintf('%02d/%s/%s',$day,substr($data['month'],5),substr($data['month'],0,4)):'' ?></td><?php foreach(BackupChecklist::ROW_FIELDS as $key=>$label): ?><td><?= $e($entry[$key]??'') ?></td><?php endforeach; ?></tr><?php endfor; ?>
</tbody></table><table class="signatures"><tr><td style="width:24%" rowspan="2"></td><td style="width:38%">Prepare By : <?= $e($data['prepared_by']) ?></td><td style="width:38%">Approve By : <?= $e($data['approved_by']) ?></td></tr><tr><td>Date : <?= $e($data['prepared_date']) ?></td><td>Date : <?= $e($data['approved_date']) ?></td></tr></table>
<p class="footer">IT-F-004 Rev.00 Issue date: 17-03-2025</p>
</body></html>
