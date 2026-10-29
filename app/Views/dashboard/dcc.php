<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/dashboard.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/dashboard.css') ?>">
<div class="dashboard">
    <section class="dash-welcome" aria-labelledby="dcc-home-title">
        <div><span class="dash-eyebrow">DOCUMENT CONTROL</span>
            <h1 id="dcc-home-title">สวัสดี คุณ<?= htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
            <p>พื้นที่ทำงาน DCC สำหรับดูแลเอกสาร KPI ของแผนก และแจ้งซ่อมติดตามงาน</p>
            <a class="dash-hero-link" href="<?= APP_URL ?>/document-control">เปิดทะเบียนเอกสาร <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
        </div>
    </section>
    <div class="dash-section-heading">
        <h2>ภาพรวมทะเบียนเอกสาร</h2><span>ข้อมูล ณ เวลาเปิดหน้านี้</span>
    </div>
    <div class="dash-stats">
        <?php foreach (['all' => 'เอกสารทั้งหมด', 'draft' => 'ฉบับร่างรอตรวจสอบ', 'active' => 'เอกสารใช้งาน', 'obsolete' => 'ยกเลิกใช้'] as $status => $label): ?>
            <a class="dash-stat" href="<?= APP_URL ?>/document-control<?= $status === 'all' ? '' : '?status=' . $status ?>"><span class="dash-icon blue"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span><strong><?= number_format($status === 'all' ? $total : ($counts[$status] ?? 0)) ?></strong><span><?= $label ?></span></a>
        <?php endforeach; ?>
    </div>
    <section class="dash-panel" aria-labelledby="dcc-shortcuts">
        <div class="dash-panel-heading">
            <h2 id="dcc-shortcuts">ทางลัดการทำงาน</h2>
        </div>
        <div class="dash-shortcuts">
            <a href="<?= APP_URL ?>/tickets/create"><span class="dash-icon blue"><i class="bi bi-tools" aria-hidden="true"></i></span><span><strong>แจ้งซ่อม</strong><small>แจ้งปัญหาและติดตามสถานะใบงานของคุณ</small></span></a>
            <a href="<?= APP_URL ?>/document-control/create"><span class="dash-icon blue"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i></span><span><strong>ลงทะเบียนเอกสาร</strong><small>เพิ่มเอกสารและรายละเอียด Revision</small></span></a>
            <a href="<?= APP_URL ?>/document-control/files"><span class="dash-icon blue"><i class="bi bi-folder2-open" aria-hidden="true"></i></span><span><strong>ไฟล์เอกสาร DCC</strong><small>เลือกไฟล์จาก SharePoint ของ DCC</small></span></a>
            <a href="<?= APP_URL ?>/users"><span class="dash-icon blue"><i class="bi bi-people" aria-hidden="true"></i></span><span><strong>รายชื่อผู้ใช้งาน</strong><small>ค้นหาและดูข้อมูลบุคลากร ไม่สามารถเพิ่ม แก้ไข หรือลบบัญชี</small></span></a>
        </div>
    </section>
</div>