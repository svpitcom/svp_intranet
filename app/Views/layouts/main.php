<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/style.css') ?>">
</head>

<body class="app-body">
    <a class="skip-link" href="#main-content">ข้ามไปเนื้อหา</a>
    <?php require BASE_PATH . '/app/Views/partials/sidebar.php'; ?>
    <div class="app-workspace">
        <?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
            <main id="main-content" class="app-content" tabindex="-1">
                <div class="workspace-caption"><span>WORKSPACE</span><span>SVP · ระบบจัดการภายใน</span></div>
                <?php if ($msg = Session::flash('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="status">
                        <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิดข้อความ"></button>
                    </div>
                <?php endif; ?>
                <?php if ($msg = Session::flash('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิดข้อความ"></button>
                    </div>
                <?php endif; ?>
                <?php if ($msg = Session::flash('errors')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิดข้อความ"></button>
                    </div>
                <?php endif; ?>
                <?= $content ?>
                <footer class="workspace-footer"><span>SV POLYMER</span><span>พื้นที่ทำงานร่วมกันของเรา</span></footer>
            </main>
    </div>
    <script src="<?= APP_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/main.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/main.js') ?>"></script>
</body>

</html>
