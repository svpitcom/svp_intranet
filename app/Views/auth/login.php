<form method="POST" action="<?= APP_URL ?>/login">
    <div class="mb-3">
        <label class="form-label" for="login-username">ชื่อผู้ใช้</label>
        <input type="text" id="login-username" name="username" class="form-control" autocomplete="username" placeholder="กรอกชื่อผู้ใช้" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label" for="login-password">รหัสผ่าน</label>
        <div class="password-field"><input type="password" id="login-password" name="password" class="form-control" autocomplete="current-password" placeholder="กรอกรหัสผ่าน" required><button type="button" class="password-toggle" aria-controls="login-password" aria-pressed="false">แสดง</button></div>
    </div>
    <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
<input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
</form>
