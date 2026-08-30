<?php $user = Session::get('user'); ?>
<nav class="navbar navbar-expand-md navbar-dark px-3 navbar-modern">
    <a class="navbar-brand navbar-brand-modern" href="<?= APP_URL ?>/">
        <i class="bi bi-hdd-network-fill me-2"></i><?= APP_NAME ?>
    </a>
    <div class="ms-auto d-flex align-items-center">
        <?php if ($user): ?>
            <div class="dropdown">
                <button class="btn user-menu-btn dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="user-avatar">
                        <?= strtoupper(mb_substr($user['first_name'], 0, 1)) ?>
                    </span>
                    <span class="d-none d-sm-flex flex-column align-items-start lh-sm">
                        <span class="user-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span>
                        <span class="user-role"><?= htmlspecialchars($user['user_role']) ?></span>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm mt-2">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="<?= APP_URL ?>/logout">
                            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</nav>

<style>
    .navbar-modern {
        background: linear-gradient(135deg, #1e3a8a, #2b6cb0);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        min-height: 64px;
    }

    .navbar-brand-modern {
        font-weight: 700;
        font-size: 1.15rem;
        color: #ffffff !important;
        display: flex;
        align-items: center;
    }

    .navbar-brand-modern i {
        font-size: 1.3rem;
        color: #a7c7ff;
    }

    .user-menu-btn {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 999px;
        padding: 0.35rem 0.9rem 0.35rem 0.35rem;
        color: #ffffff;
        transition: background 0.2s ease;
    }

    .user-menu-btn:hover,
    .user-menu-btn:focus {
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
    }

    .user-menu-btn::after {
        margin-left: 0.4rem;
        vertical-align: 0;
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #ffffff;
        color: #2b6cb0;
        font-weight: 700;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .user-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: #ffffff;
    }

    .user-role {
        font-size: 0.72rem;
        color: #cbd8f0;
    }

    .dropdown-menu {
        border: none;
        border-radius: 10px;
        min-width: 180px;
    }

    .dropdown-item {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }

    .dropdown-item:hover {
        background: #f4f6fb;
        color: #2b6cb0;
    }

    .dropdown-item i {
        color: #e53e3e;
    }
</style>