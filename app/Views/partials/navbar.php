<?php $user = Session::get('user'); ?>
<nav class="navbar navbar-expand-md navbar-dark bg-dark px-3">
    <a class="navbar-brand" href="<?= APP_URL ?>/"><?= APP_NAME ?></a>
    <div class="ms-auto d-flex align-items-center text-light">
        <?php if ($user): ?>
            <span class="me-3">
                <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                <span class="badge bg-secondary ms-1"><?= htmlspecialchars($user['user_role']) ?></span>
            </span>
            <a href="<?= APP_URL ?>/logout" class="btn btn-outline-light btn-sm">ออกจากระบบ</a>
        <?php endif; ?>
    </div>
</nav>