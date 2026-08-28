<form method="POST" action="<?= APP_URL ?>/login">
    <div class="mb-3">
        <label class="form-label">ชื่อผู้ใช้</label>
        <input type="text" name="username" class="form-control" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label">รหัสผ่าน</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
</form>