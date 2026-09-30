<?php $isEdit = $user !== null; ?>
<div class="mb-4">
    <h4 class="fw-bold mb-1"><?= $isEdit ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้ใหม่' ?></h4>
    <span class="text-muted small"><?= $isEdit ? 'แก้ไขข้อมูลบัญชีผู้ใช้งาน' : 'กรอกข้อมูลเพื่อสร้างบัญชีผู้ใช้งานใหม่' ?></span>
</div>

<form method="POST"
    action="<?= APP_URL ?><?= $isEdit ? '/users/' . $user['svp_user_id'] . '/edit' : '/users/create' ?>"
    class="form-card" style="max-width: 680px;">

    <div class="form-section">
        <div class="section-label"><i class="bi bi-person-fill"></i> ข้อมูลส่วนตัว</div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label-modern">ชื่อ</label>
                <input type="text" name="first_name" class="form-control form-control-modern" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label-modern">นามสกุล</label>
                <input type="text" name="last_name" class="form-control form-control-modern" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="section-label"><i class="bi bi-shield-lock-fill"></i> ข้อมูลบัญชี</div>
        <div class="mb-3">
            <label class="form-label-modern">ชื่อผู้ใช้ (username)</label>
            <div class="input-group-modern">
                <span class="input-icon"><i class="bi bi-at"></i></span>
                <input type="text" name="username" class="form-control form-control-modern" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label-modern">อีเมล</label>
            <div class="input-group-modern">
                <span class="input-icon"><i class="bi bi-envelope-fill"></i></span>
                <input type="email" name="email" class="form-control form-control-modern" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
            </div>
        </div>

        <div class="mb-1">
            <label class="form-label-modern">
                รหัสผ่าน
                <?php if ($isEdit): ?><span class="text-muted fw-normal">(เว้นว่างไว้หากไม่ต้องการเปลี่ยน)</span><?php endif; ?>
            </label>
            <div class="input-group-modern">
                <span class="input-icon"><i class="bi bi-key-fill"></i></span>
                <input type="password" name="password" class="form-control form-control-modern" <?= $isEdit ? '' : 'required' ?>>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="section-label"><i class="bi bi-diagram-3-fill"></i> สังกัดและสิทธิ์การใช้งาน</div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label-modern">แผนก</label>
                <select name="svp_department_id" class="form-select form-control-modern">
                    <option value="">-- ไม่ระบุ --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['svp_department_id'] ?>" <?= (($user['svp_department_id'] ?? null) == $d['svp_department_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['svp_department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label-modern">ตำแหน่ง</label>
                <select name="svp_position_id" class="form-select form-control-modern">
                    <option value="">-- ไม่ระบุ --</option>
                    <?php foreach ($positions as $p): ?>
                        <option value="<?= $p['svp_position_id'] ?>" <?= (($user['svp_position_id'] ?? null) == $p['svp_position_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['position_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <label class="form-label-modern">สิทธิ์การใช้งาน (role)</label>
                <select name="user_role" class="form-select form-control-modern">
                    <?php foreach (['user', 'manager', 'admin'] as $role): ?>
                        <option value="<?= $role ?>" <?= (($user['user_role'] ?? 'user') === $role) ? 'selected' : '' ?>><?= $role ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($isEdit): ?>
                <div class="col-md-6 d-flex align-items-center">
                    <label class="switch-modern mt-4">
                        <input type="checkbox" name="is_active" value="1" id="is_active" <?= !empty($user['is_active']) ? 'checked' : '' ?>>
                        <span class="switch-track"></span>
                        <span class="switch-text">เปิดใช้งานบัญชีนี้</span>
                    </label>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex gap-2 pt-2">
        <button type="submit" class="btn-primary-modern">
            <i class="bi bi-check-lg"></i> <?= $isEdit ? 'บันทึกการแก้ไข' : 'บันทึก' ?>
        </button>
        <a href="<?= APP_URL ?>/users" class="btn-secondary-modern">ยกเลิก</a>
    </div>
<input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
</form>
