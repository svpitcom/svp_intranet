<?php $e=static fn($v)=>nl2br(Portal::esc((string)$v)); ?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><style>
<?php require __DIR__.'/_fonts.php'; ?>
@page { margin:26px; }
body { font-family:Sarabun,sans-serif; font-size:10px; line-height:1.2; color:#111; }
h1 { font-size:15px; text-align:center; margin:0 0 4px; }
.company { text-align:center; margin:0 0 10px; }
table { border-collapse:collapse; width:100%; table-layout:fixed; }
td,th { border:1px solid #555; padding:1px 3px; word-wrap:break-word; vertical-align:middle; }
th { background:#edf2f5; text-align:center; }
thead { display:table-header-group; }
tr { page-break-inside:avoid; }
.number { width:4%; text-align:center; }
.check { width:5%; text-align:center; }
.footer { font-size:9px; text-align:right; margin-top:8px; }
.secondary { page-break-before:always; }
.notes { white-space:normal; word-wrap:break-word; }
</style></head><body>
<h1><?= PmInspection::TITLE ?></h1><p class="company">SV POLYMER CO., LTD.</p>
<table><tr><td style="width:60%">ชื่อเครื่องคอมพิวเตอร์: <?= $e($data['device_name']) ?><br>หมายเลขเครื่อง: <?= $e($data['serial_number']) ?><br>แผนก / ฝ่าย: <?= $e($data['department']) ?></td><td>ผู้จัดการแผนกไอที: <?= $e($data['manager']) ?><br>ผู้รับผิดชอบการตรวจสอบ: <?= $e($data['inspector']) ?></td></tr><tr><td>วันที่ตรวจสอบ: <?= $e($data['performed_date']) ?><br>เวลา <?= $e($data['start_time']) ?> ถึง <?= $e($data['end_time']) ?> น. · รวม <?= $data['start_time']!=='' && $data['end_time']!==''?PmInspection::minutes($data['start_time'],$data['end_time']):'—' ?> นาที</td><td>ผู้ใช้งานรับทราบ: <?= $e($data['acknowledged_by']) ?></td></tr></table>
<p>ชื่อที่ระบุเป็นข้อมูลในเอกสาร ไม่ใช่ลายเซ็นหรือการอนุมัติผ่านระบบ</p>
<?php foreach(PmInspection::SECTIONS as $section=>[$title,$start,$end,$fields]): ?>
<div class="<?= $section==='work'?'secondary':'' ?>"><h3><?= $e($title) ?></h3>
<table><thead><tr><th class="number">แถว</th>
<?php if($section==='checks'): ?><th style="width:16%">หัวข้อ</th><th style="width:17%">วิธีการตรวจสอบ</th><th style="width:16%">มาตรฐาน</th><th class="check">มี</th><th class="check">ไม่มี</th>
<?php elseif($section==='work'): ?><th style="width:22%">รายการ</th><th style="width:25%">รายละเอียดการดำเนินงาน</th><th class="check">เสร็จ</th><th style="width:7%">ไม่เสร็จ</th>
<?php else: ?><th style="width:48%">รายละเอียดการตรวจสอบด้านความปลอดภัย</th><th style="width:11%">สถานะ</th><?php endif; ?>
<th class="check">ปกติ</th><th style="width:7%;text-align:center">ผิดปกติ</th><th>รายละเอียด / หมายเหตุ</th></tr></thead><tbody>
<?php for($r=$start;$r<=$end;$r++): $row=$data[$section][$r]; ?><tr><td class="number"><?= $r ?></td><td><?= $e($row['topic']) ?></td>
<?php if($section==='checks'): ?><td><?= $e($row['method']) ?></td><td><?= $e($row['standard']) ?></td><td class="check"><?= $row['status']==='present'?'X':'' ?></td><td class="check"><?= $row['status']==='absent'?'X':'' ?></td>
<?php elseif($section==='work'): ?><td><?= $e($row['detail']) ?></td><td class="check"><?= $row['status']==='done'?'X':'' ?></td><td class="check"><?= $row['status']==='pending'?'X':'' ?></td>
<?php else: ?><td><?= $e($row['status']) ?></td><?php endif; ?>
<td class="check"><?= $row['result']==='normal'?'X':'' ?></td><td class="check"><?= $row['result']==='abnormal'?'X':'' ?></td><td><?= $e($row['notes']) ?></td></tr><?php endfor; ?>
</tbody></table></div>
<?php if($section==='checks'): ?><p class="footer"><?= PmInspection::REVISION ?></p><?php endif; ?>
<?php endforeach; ?>
<p><strong>สรุปผล:</strong> <?= $e(['completed'=>'ทำครบ / ปกติดี','partial'=>'ทำได้บางส่วน','issue_found'=>'พบปัญหา'][$data['result_status']]??'ยังไม่ได้บันทึกผล') ?></p>
<p class="notes">หมายเหตุ: <?= $e($data['notes']??'') ?></p><p>ผู้จัดการแผนกไอที ลงชื่อ ____________________ ผู้ตรวจสอบ ลงชื่อ ____________________</p>
<p class="footer">Control Copy Lay Out : IT · <?= $e($data['revision']??PmInspection::REVISION) ?></p>
</body></html>
