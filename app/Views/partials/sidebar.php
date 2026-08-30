<?php $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
<nav class="col-md-2 d-none d-md-block sidebar-modern py-4">
    <div class="px-3 mb-3">
        <span class="sidebar-heading">เมนูจัดการระบบ</span>
    </div>
    <ul class="nav flex-column px-2">
        <li class="nav-item mb-1">
            <a class="nav-link-modern <?= str_starts_with($currentPath, '/users') ? 'active' : '' ?>" href="<?= APP_URL ?>/users">
                <i class="bi bi-people-fill"></i>
                <span>จัดการผู้ใช้งาน</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link-modern <?= str_starts_with($currentPath, '/departments') ? 'active' : '' ?>" href="<?= APP_URL ?>/departments">
                <i class="bi bi-diagram-3-fill"></i>
                <span>จัดการแผนก</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link-modern <?= str_starts_with($currentPath, '/positions') ? 'active' : '' ?>" href="<?= APP_URL ?>/positions">
                <i class="bi bi-award-fill"></i>
                <span>จัดการตำแหน่ง</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link-modern <?= str_starts_with($currentPath, '/device_types') ? 'active' : '' ?>" href="<?= APP_URL ?>/device_types">
                <i class="bi bi-tags-fill"></i>
                <span>จัดการประเภทอุปกรณ์</span>
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link-modern <?= str_starts_with($currentPath, '/devices') ? 'active' : '' ?>" href="<?= APP_URL ?>/devices">
                <i class="bi bi-laptop"></i>
                <span>จัดการอุปกรณ์</span>
            </a>
        </li>
    </ul>
</nav>

<style>
.sidebar-modern {
    background: #ffffff;
    border-right: 1px solid #eef0f2;
    min-height: 100vh;
}

.sidebar-heading {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: #9aa1ac;
    text-transform: uppercase;
}

.nav-link-modern {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 0.9rem;
    border-radius: 10px;
    color: #4a5568;
    font-size: 0.92rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.nav-link-modern i {
    font-size: 1.05rem;
    color: #9aa1ac;
    transition: color 0.2s ease;
    width: 20px;
    text-align: center;
}

.nav-link-modern:hover {
    background: #f4f6fb;
    color: #2b6cb0;
}

.nav-link-modern:hover i {
    color: #2b6cb0;
}

.nav-link-modern.active {
    background: linear-gradient(135deg, #4f7cff, #2b6cb0);
    color: #ffffff;
    box-shadow: 0 4px 10px rgba(43, 108, 176, 0.25);
}

.nav-link-modern.active i {
    color: #ffffff;
}
</style>