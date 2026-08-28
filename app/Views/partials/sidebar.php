<?php $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
<nav class="col-md-2 d-none d-md-block bg-white border-end sidebar py-4">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= str_starts_with($currentPath, '/users') ? 'active' : '' ?>" href="<?= APP_URL ?>/users">
                จัดการผู้ใช้งาน
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= str_starts_with($currentPath, '/departments') ? 'active' : '' ?>" href="<?= APP_URL ?>/departments">
                จัดการแผนก
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= str_starts_with($currentPath, '/positions') ? 'active' : '' ?>" href="<?= APP_URL ?>/positions">
                จัดการตำแหน่ง
            </a>
        </li>
    </ul>
</nav>