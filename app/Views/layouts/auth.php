<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>

<body class="bg-light">
    <div class="d-flex align-items-center justify-content-center vh-100">
        <div class="card shadow-sm" style="width: 100%; max-width: 380px;">
            <div class="card-body p-4">
                <h4 class="card-title text-center mb-4"><?= APP_NAME ?></h4>
                <?php if ($msg = Session::flash('error')): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <?= $content ?>
            </div>
        </div>
    </div>
    <script src="<?= APP_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>