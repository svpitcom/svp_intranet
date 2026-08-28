<?php $isEdit = $user !== null; ?>
<h4 class="mb-3"><?= $isEdit ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้ใหม่' ?></h4>
<form method="POST"
    action="<?= APP_URL ?><?= $isEdit ? '/users/' . $user['svp_user_id'] . '/edit' : '/users/create' ?>"
    class="card p-4" style="max-width: 640px;">

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">ชื่อ</label>
            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">นามสกุล</label>
            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">ชื่อผู้ใช้ (username)</label>
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">อีเมล</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">รหัสผ่าน <?= $isEdit ? '(เว้นว่างไว้หากไม่ต้องการเปลี่ยน)' : '' ?></label>
        <input type="password" name="password" class="form-control" <?= $isEdit ? '' : 'required' ?>>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">แผนก</label>
            <select name="svp_department_id" class="form-select">
                <option value="">-- ไม่ระบุ --</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['svp_department_id'] ?>" <?= (($user['svp_department_id'] ?? null) == $d['svp_department_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['svp_department_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">ตำแหน่ง</label>
            <select name="svp_position_id" class="form-select">
                <option value="">-- ไม่ระบุ --</option>
                <?php foreach ($positions as $p): ?>
                    <option value="<?= $p['svp_position_id'] ?>" <?= (($user['svp_position_id'] ?? null) == $p['svp_position_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['position_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">สิทธิ์การใช้งาน (role)</label>
            <select name="user_role" class="form-select">
                <?php foreach (['user', 'manager', 'admin'] as $role): ?>
                    <option value="<?= $role ?>" <?= (($user['user_role'] ?? 'user') === $role) ? 'selected' : '' ?>><?= $role ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($isEdit): ?>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" <?= !empty($user['is_active']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">เปิดใช้งานบัญชีนี้</label>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'บันทึกการแก้ไข' : 'บันทึก' ?></button>
        <a href="<?= APP_URL ?>/users" class="btn btn-outline-secondary">ยกเลิก</a>
    </div>
</form>