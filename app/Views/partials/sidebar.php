<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?: '', '/');
if ($basePath !== '' && str_starts_with($currentPath, $basePath . '/')) $currentPath = substr($currentPath, strlen($basePath));
$navGroups = [
    'บุคลากร' => [['/users', 'ผู้ใช้งาน', 'people'], ['/departments', 'แผนก', 'diagram-3'], ['/positions', 'ตำแหน่ง', 'person-badge']],
    'อุปกรณ์' => [['/devices', 'ทะเบียนอุปกรณ์', 'laptop'], ['/device_types', 'ประเภทอุปกรณ์', 'grid']],
    'งานบำรุงรักษา' => [['/pm-schedules', 'แผนบำรุงรักษา', 'calendar2-check'], ['/pm-records', 'ประวัติการทำ PM', 'clock-history']],
];
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
                $active = $currentPath === $path || str_starts_with($currentPath, $path . '/') || ($path === '/users' && $currentPath === '/');
            ?>
                <li class="nav-item"><a class="nav-link-modern <?= $active ? 'active' : '' ?>" href="<?= APP_URL . $path ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                    <i class="bi bi-<?= $icon ?>" aria-hidden="true"></i><span><?= $label ?></span>
                    <?php if ($active): ?><span class="nav-indicator" aria-hidden="true"></span><?php endif; ?>
                </a></li>
            <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
        </nav>
        <div class="sidebar-note"><span class="sidebar-note-mark">SVP</span><p>ดูแลคน ดูแลอุปกรณ์<br><strong>ให้ทุกวันทำงานได้ดีขึ้น</strong></p></div>
    </div>
</aside>
