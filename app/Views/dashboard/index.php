<?php
$escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$cards = [
    ['users', 'ผู้ใช้งานที่เปิดใช้งาน', 'people', '/users', 'teal'],
    ['devices', 'อุปกรณ์ที่เปิดใช้งาน', 'laptop', '/devices', 'blue'],
    ['overdue', 'แผน PM เกินกำหนด', 'exclamation-circle', '/pm-schedules', 'orange'],
    ['upcoming', 'แผน PM ภายใน 7 วัน', 'calendar2-check', '/pm-schedules', 'teal'],
];
$shortcuts = [
    ['/devices', 'ทะเบียนอุปกรณ์', 'ค้นหาอุปกรณ์และผู้รับผิดชอบ', 'laptop'],
    ['/users', 'รายชื่อผู้ใช้งาน', 'ข้อมูลบุคลากรในองค์กร', 'people'],
    ['/pm-schedules', 'แผนบำรุงรักษา', 'ตรวจสอบกำหนดและบันทึก PM', 'calendar2-check'],
    ['/pm-records', 'ประวัติการทำ PM', 'ดูผลการตรวจและเอกสารแนบ', 'clock-history'],
];
if (DocumentControlAccess::allows($user)) {
    $shortcuts[] = ['/document-control', 'Document Control', 'ทะเบียนเอกสารของ DCC', 'file-earmark-check'];
}
if (($user['user_role'] ?? '') === 'admin') {
    $shortcuts[] = ['/sharepoint', 'SharePoint', 'จัดการการเชื่อมต่อและส่งออกข้อมูล', 'cloud'];
}
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/dashboard.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/dashboard.css') ?>">
<div class="dashboard">
    <section class="dash-welcome" aria-labelledby="dashboard-title">
        <div><span class="dash-eyebrow">SVP · YOUR WORKSPACE</span>
            <h1 id="dashboard-title">สวัสดี คุณ<?= $escape($user['first_name'] ?? '') ?></h1>
            <p>พร้อมเริ่มต้นวันทำงานแล้วหรือยัง? ดูภาพรวมและเข้าถึงงานของคุณได้จากที่นี่</p>
            <a class="dash-hero-link" href="<?= APP_URL ?>/pm-schedules">ไปที่แผนบำรุงรักษา <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="dash-date"><i class="bi bi-calendar3" aria-hidden="true"></i><span>วันนี้ · เวลาไทย</span><strong><?= $escape(date('d/m/', strtotime($today)) . ((int) substr($today, 0, 4) + 543)) ?></strong></div>
    </section>
    <div class="dash-section-heading"><h2>ภาพรวมองค์กร</h2><span>ข้อมูล ณ เวลาเปิดหน้านี้</span></div>
    <div class="dash-stats">
        <?php foreach ($cards as [$key, $label, $icon, $path, $color]): ?>
        <a class="dash-stat" href="<?= APP_URL . $path ?>"><span class="dash-icon <?= $color ?>"><i class="bi bi-<?= $icon ?>" aria-hidden="true"></i></span><strong><?= number_format($stats[$key]) ?></strong><span><?= $label ?></span></a>
        <?php endforeach; ?>
    </div>
    <div class="dash-columns">
        <section class="dash-panel" aria-labelledby="dash-pm-title">
            <div class="dash-panel-heading"><div><h2 id="dash-pm-title">งาน PM ที่ควรติดตาม</h2><p>เกินกำหนดและครบกำหนดใน 7 วันรวมวันนี้ · แสดงสูงสุด 6 รายการ</p></div><a href="<?= APP_URL ?>/pm-schedules">ดูทั้งหมด <span aria-hidden="true">↗</span></a></div>
            <?php if (!$schedules): ?>
                <div class="dash-empty"><i class="bi bi-check2-circle" aria-hidden="true"></i><h3>ไม่มีงาน PM ที่ถึงกำหนดในช่วงนี้</h3><p>ตรวจสอบแผนล่วงหน้าเพิ่มเติมได้ที่หน้าแผนบำรุงรักษา</p></div>
            <?php else: ?>
                <div class="dash-tasks">
                <?php foreach ($schedules as $schedule): $overdue = $schedule['next_pm_date'] < $today; ?>
                    <div class="dash-task"><div class="dash-task-info"><span class="dash-status <?= $overdue ? 'late' : '' ?>"><?= $overdue ? 'เกินกำหนด' : 'ใกล้ถึงกำหนด' ?></span><h3><?= $escape($schedule['pm_title']) ?></h3><p><?= $escape($schedule['svp_device_name'] ?? 'ไม่พบอุปกรณ์') ?> · <?= $escape(trim(($schedule['first_name'] ?? '') . ' ' . ($schedule['last_name'] ?? '')) ?: 'ยังไม่ระบุผู้รับผิดชอบ') ?></p><small>ครบกำหนด <?= $escape(date('d/m/Y', strtotime($schedule['next_pm_date']))) ?></small></div><a class="dash-task-link" href="<?= APP_URL ?>/pm-schedules/<?= (int) $schedule['pm_schedule_id'] ?>/record" aria-label="บันทึก PM <?= $escape($schedule['pm_title']) ?>">บันทึก PM <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <section class="dash-panel" aria-labelledby="dash-links-title"><div class="dash-panel-heading"><div><h2 id="dash-links-title">ทางลัดการทำงาน</h2><p>เลือกส่วนงานที่ต้องการ</p></div></div>
            <div class="dash-shortcuts"><?php foreach ($shortcuts as [$path, $label, $description, $icon]): ?><a href="<?= APP_URL . $path ?>"><span class="dash-icon teal"><i class="bi bi-<?= $icon ?>" aria-hidden="true"></i></span><span><strong><?= $label ?></strong><small><?= $description ?></small></span><i class="bi bi-chevron-right" aria-hidden="true"></i></a><?php endforeach; ?></div>
        </section>
    </div>
</div>
