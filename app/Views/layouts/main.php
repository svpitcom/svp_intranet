<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>

<body>
    <?php require BASE_PATH . '/app/Views/partials/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php require BASE_PATH . '/app/Views/partials/sidebar.php'; ?>
            <main class="col-md-10 ms-sm-auto px-4 py-4">
                <?php if ($msg = Session::flash('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($msg = Session::flash('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($msg = Session::flash('errors')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?= $content ?>
            </main>
        </div>
    </div>
    <script src="<?= APP_URL ?>/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
</body>

</html>