<!-- Device Form -->
<?php
$isEdit = isset($device) && $device !== null;
$currentUser = Session::get('user');
$errors = Session::get('errors');
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1"><?= $isEdit ? 'แก้ไขอุปกรณ์' : 'เพิ่มอุปกรณ์ใหม่' ?></h4>
    <span class="text-muted small"><?= $isEdit ? 'แก้ไขข้อมูลอุปกรณ์ในระบบ' : 'กรอกข้อมูลเพื่อเพิ่มอุปกรณ์ใหม่' ?></span>
</div>

<?php if ($errors): ?>
    <div class="alert-modern alert-danger-modern align-items-start">
        <i class="bi bi-exclamation-circle-fill mt-1"></i>
        <div>
            <?php if (is_array($errors)): ?>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <?= htmlspecialchars($errors) ?>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<form method="POST"
    action="<?= APP_URL ?><?= $isEdit ? '/devices/' . $device['svp_device_id'] . '/edit' : '/devices/create' ?>"
    class="form-card" style="max-width: 680px;">

    <div class="form-section">
        <div class="section-label"><i class="bi bi-laptop"></i> ข้อมูลอุปกรณ์</div>

        <div class="mb-3">
            <label for="svp_device_name" class="form-label-modern">ชื่ออุปกรณ์</label>
            <input
                type="text"
                name="svp_device_name"
                id="svp_device_name"
                class="form-control form-control-modern"
                value="<?= htmlspecialchars($device['svp_device_name'] ?? '') ?>"
                required
                autofocus>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="brand_name" class="form-label-modern">ยี่ห้อ</label>
                <input
                    type="text"
                    name="brand_name"
                    id="brand_name"
                    class="form-control form-control-modern"
                    value="<?= htmlspecialchars($device['brand_name'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="model_name" class="form-label-modern">รุ่น</label>
                <input
                    type="text"
                    name="model_name"
                    id="model_name"
                    class="form-control form-control-modern"
                    value="<?= htmlspecialchars($device['model_name'] ?? '') ?>">
            </div>
        </div>

        <div class="mb-1">
            <label for="serial_number" class="form-label-modern">หมายเลข Serial Number</label>
            <div class="input-group-modern">
                <span class="input-icon"><i class="bi bi-upc-scan"></i></span>
                <input
                    type="text"
                    name="serial_number"
                    id="serial_number"
                    class="form-control form-control-modern"
                    value="<?= htmlspecialchars($device['serial_number'] ?? '') ?>"
                    required>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="section-label"><i class="bi bi-diagram-3-fill"></i> การจัดสรร</div>

        <div class="mb-3">
            <label for="device_type_id" class="form-label-modern">ประเภทอุปกรณ์</label>
            <select name="device_type_id" id="device_type_id" class="form-select form-control-modern">
                <option value="">-- เลือกประเภทอุปกรณ์ --</option>
                <?php foreach ($deviceTypes as $type): ?>
                    <option value="<?= $type['device_type_id'] ?>"
                        <?= (($device['device_type_id'] ?? null) == $type['device_type_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($type['device_type_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="svp_department_id" class="form-label-modern">แผนก</label>
            <select name="svp_department_id" id="svp_department_id" class="form-select form-control-modern">
                <option value="">-- เลือกแผนก --</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['svp_department_id'] ?>"
                        <?= (($device['svp_department_id'] ?? null) == $dept['svp_department_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['svp_department_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-1">
            <label for="svp_user_id" class="form-label-modern">ผู้ถือครอง</label>
            <select name="svp_user_id" id="svp_user_id" class="form-select form-control-modern">
                <option value="">-- ไม่ระบุผู้ถือครอง --</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= $user['svp_user_id'] ?>"
                        <?= (($device['svp_user_id'] ?? null) == $user['svp_user_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($isEdit): ?>
        <div class="form-section" style="border-bottom: none; margin-bottom: 0.5rem; padding-bottom: 0;">
            <label class="switch-modern">
                <input type="checkbox" name="is_active" id="is_active" value="1" <?= !empty($device['is_active']) ? 'checked' : '' ?>>
                <span class="switch-track"></span>
                <span class="switch-text">ใช้งานอยู่</span>
            </label>
        </div>
    <?php endif; ?>

    <div class="d-flex gap-2 pt-2">
        <button type="submit" class="btn-primary-modern">
            <i class="bi bi-check-lg"></i> <?= $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มอุปกรณ์' ?>
        </button>
        <a href="<?= APP_URL ?>/devices" class="btn-secondary-modern">ยกเลิก</a>
    </div>
<input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
</form>
