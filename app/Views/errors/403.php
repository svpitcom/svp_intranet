<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - ไม่มีสิทธิ์เข้าถึง</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/style.css') ?>">
</head>

<body class="d-flex align-items-center justify-content-center vh-100 bg-light">
    <div class="text-center card p-5 mx-3">
        <h1 class="display-4">403</h1>
        <p class="text-muted">คุณไม่มีสิทธิ์เข้าถึงหน้านี้</p>
        <a href="<?= APP_URL ?>/" class="btn btn-primary">กลับหน้าแรก</a>
    </div>
</body>

</html>
