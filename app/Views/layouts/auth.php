<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/style.css') ?>">
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-story" aria-label="SVP Intranet">
            <div class="brand-lockup"><span class="brand-symbol">S<span>V</span></span><span><strong>SVP INTRANET</strong><small>PEOPLE · ASSETS · CARE</small></span></div>
            <div class="auth-story-copy"><span class="eyebrow">CONNECTED AT WORK</span><h1>ทุกงานเชื่อมถึงกัน<br>ทุกวันเดินหน้าไปด้วยกัน</h1><p>พื้นที่กลางสำหรับจัดการบุคลากร อุปกรณ์<br>และงานบำรุงรักษาของ SV POLYMER</p></div>
            <div class="auth-story-footer"><span>01 / บุคลากร</span><span>02 / อุปกรณ์</span><span>03 / บำรุงรักษา</span></div>
        </section>
        <section class="auth-panel">
            <div class="auth-form-wrap">
                <span class="eyebrow">WELCOME BACK</span><h2>ยินดีต้อนรับกลับ</h2>
                <p class="text-muted mb-4">เข้าสู่ระบบเพื่อเริ่มต้นวันทำงานของคุณ</p>
                <?php if ($msg = Session::flash('error')): ?><div class="alert alert-danger" role="alert"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                <?= $content ?>
                <div class="auth-help">หากไม่สามารถเข้าสู่ระบบได้ กรุณาติดต่อผู้ดูแลระบบ</div>
            </div>
            <small class="auth-copyright">SV POLYMER · ระบบสำหรับบุคลากรภายใน</small>
        </section>
    </main>
    <script src="<?= APP_URL ?>/assets/js/main.js?v=<?= filemtime(BASE_PATH . '/public/assets/js/main.js') ?>"></script>
</body>
</html>
