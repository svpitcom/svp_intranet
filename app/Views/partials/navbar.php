<?php
$navUser = Session::get('user');
$sectionName = 'หน้าแรก';
foreach ($navGroups as $items) {
    foreach ($items as [$path, $label]) {
        if ($currentPath === $path || str_starts_with($currentPath, $path . '/')) $sectionName = $label;
    }
}
$roleNames = ['admin' => 'ผู้ดูแลระบบ', 'manager' => 'ผู้จัดการ', 'user' => 'ผู้ใช้งาน'];
?>
<header class="app-topbar">
    <div class="d-flex align-items-center gap-3">
        <button class="menu-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#app-sidebar" aria-controls="app-sidebar" aria-label="เปิดเมนูหลัก"><span aria-hidden="true">☰</span></button>
        <div class="topbar-breadcrumb"><span>พื้นที่ทำงาน</span><span class="breadcrumb-divider">/</span><strong><?= htmlspecialchars($sectionName) ?></strong></div>
    </div>
    <?php if ($navUser): ?>
    <div class="dropdown">
        <button class="user-menu-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="เมนูบัญชีผู้ใช้">
            <span class="user-avatar"><?= htmlspecialchars(mb_substr($navUser['first_name'], 0, 1)) ?></span>
            <span class="user-identity"><strong><?= htmlspecialchars($navUser['first_name'] . ' ' . $navUser['last_name']) ?></strong><small><?= $roleNames[$navUser['user_role']] ?? 'ผู้ใช้งาน' ?></small></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end mt-2">
            <li><span class="dropdown-header">บัญชีผู้ใช้งาน</span></li>
            <li><form method="POST" action="<?= APP_URL ?>/logout">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>ออกจากระบบ</button>
            </form></li>
        </ul>
    </div>
    <?php endif; ?>
</header>
