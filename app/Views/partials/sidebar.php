<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?: '', '/');
if ($basePath !== '' && $currentPath === $basePath) $currentPath = '/';
if ($basePath !== '' && str_starts_with($currentPath, $basePath . '/')) $currentPath = substr($currentPath, strlen($basePath));
$navGroups = [
    'ภาพรวม' => [['/', 'หน้าแรก', 'house-door'], ['/announcements', 'ประกาศภายใน', 'megaphone'], ['/notifications', 'การแจ้งเตือน', 'bell']],
    'บุคลากร' => [['/users', 'ผู้ใช้งาน', 'people'], ['/departments', 'แผนก', 'diagram-3'], ['/positions', 'ตำแหน่ง', 'person-badge']],
    'อุปกรณ์' => [['/devices', 'ทะเบียนอุปกรณ์', 'laptop'], ['/device_types', 'ประเภทอุปกรณ์', 'grid']],
    'งานบำรุงรักษา' => [['/tickets', 'แจ้งซ่อม / ติดตามงาน', 'tools'], ['/pm-schedules', 'แผนบำรุงรักษา', 'calendar2-check'], ['/pm-records', 'ประวัติการทำ PM', 'clock-history']],
    'คำขอและอนุมัติ' => [['/requests', 'คำขออนุมัติ', 'clipboard-check']],
];
if (MachineAccess::allows(Session::get('user'))) {
    $navGroups['เครื่องจักร']=[['/machines','ทะเบียนเครื่องจักร','gear'],['/machine-pm','แผน PM เครื่องจักร','calendar2-check'],['/machine-pm/records','ประวัติ PM เครื่องจักร','clock-history']];
}
if (DccWorkspace::restricted(Session::get('user'))) {
    $navGroups = [
        'พื้นที่ทำงาน DCC' => [['/', 'หน้าแรก DCC', 'house-door'], ['/users', 'รายชื่อผู้ใช้งาน', 'people']],
        'งานแจ้งซ่อม' => [['/tickets', 'แจ้งซ่อม / ติดตามงาน', 'tools'], ['/notifications', 'การแจ้งเตือนงานซ่อม', 'bell']],
    ];
}
if (KpiPolicy::allows(Session::get('user'))) {
    $navGroups['ผลการดำเนินงาน'] = [['/kpi', 'KPI แผนก', 'graph-up-arrow']];
}
if (ItDeviceAccess::allows(Session::get('user'))) {
    $navGroups['แบบฟอร์ม IT'] = [['/it-backup', 'ตรวจ Backup Server', 'hdd-network']];
}
if (DocumentControlAccess::allows(Session::get('user'))) {
    $navGroups['เอกสาร'] = [['/document-control', 'Document Control', 'file-earmark-check']];
}
if ((Session::get('user')['user_role'] ?? '') === 'admin') {
    $navGroups['เชื่อมต่อระบบ'] = [['/sharepoint', 'SharePoint', 'cloud']];
} elseif (DccWorkspace::restricted(Session::get('user')) && DocumentControlAccess::allows(Session::get('user'))) {
    $navGroups['เชื่อมต่อระบบ'] = [['/document-control/files', 'SharePoint DCC', 'cloud']];
}
?>
<aside class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="app-sidebar" aria-labelledby="sidebar-title">
    <div class="sidebar-brand">
        <a href="<?= APP_URL ?>/" class="brand-lockup" aria-label="SVP Intranet หน้าแรก">
            <span class="brand-symbol">S<span>V</span></span>
            <span><strong id="sidebar-title">SVP INTRANET</strong><small>PEOPLE · ASSETS · CARE</small></span>
        </a>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#app-sidebar" aria-label="ปิดเมนู"></button>
    </div>
    <div class="offcanvas-body sidebar-body">
        <nav aria-label="เมนูหลัก">
            <?php foreach ($navGroups as $heading => $items): ?>
                <div class="nav-group-label"><?= $heading ?></div>
                <ul class="nav flex-column">
                    <?php foreach ($items as [$path, $label, $icon]):
                        $active = $currentPath === $path || ($path !== '/' && str_starts_with($currentPath, $path . '/'));
                        if ($path === '/devices' && $currentPath === '/it-devices') $active = true;
                        if ($path==='/machine-pm' && str_starts_with($currentPath,'/machine-pm/records')) $active=false;
                        if ($path === '/document-control' && $currentPath === '/document-control/files' && DccWorkspace::restricted(Session::get('user'))) $active = false;
                    ?>
                        <li class="nav-item">
                            <?php if ($path === '/document-control'): ?>
                                <details class="dcc-nav-group" <?= $active ? 'open' : '' ?>>
                                    <summary title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>" class="nav-link-modern <?= $active ? 'active' : '' ?>"><i class="bi bi-<?= $icon ?>" aria-hidden="true"></i><span><?= $label ?></span><i class="bi bi-chevron-right dcc-nav-chevron" aria-hidden="true"></i></summary>
                                    <div class="ms-3 me-2 mb-2">
                                        <?php foreach (['' => '0.รวมทะเบียนเอกสาร'] + DocumentCategory::LABELS as $categoryKey => $categoryLabel): ?>
                                            <?php $categoryActive = $currentPath === '/document-control' && ($_GET['category'] ?? '') === $categoryKey; ?>
                                            <a class="nav-link-modern py-1 small <?= $categoryActive ? 'active' : '' ?>" <?= $categoryActive ? 'aria-current="page"' : '' ?> href="<?= APP_URL ?>/document-control?category=<?= rawurlencode($categoryKey) ?>"><?= htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8') ?></a>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                            <?php else: ?>
                                <a title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>" class="nav-link-modern <?= $active ? 'active' : '' ?>" href="<?= APP_URL . $path ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                                    <i class="bi bi-<?= $icon ?>" aria-hidden="true"></i><span><?= $label ?></span>
                                    <?php if ($active): ?><span class="nav-indicator" aria-hidden="true"></span><?php endif; ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-note"><span class="sidebar-note-mark">SVP</span>
            <p>ดูแลคน ดูแลอุปกรณ์<br><strong>ให้ทุกวันทำงานได้ดีขึ้น</strong></p>
        </div>
    </div>
</aside>
