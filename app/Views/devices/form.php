<!-- Device Form -->
<?php
$isEdit = isset($devices) && $devices !== null;
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
    action="<?= APP_URL ?><?= $isEdit ? '/devices/' . $devices['svp_device_id'] . '/update' : '/devices/create' ?>"
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
                value="<?= htmlspecialchars($devices['svp_device_name'] ?? '') ?>"
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
                    value="<?= htmlspecialchars($devices['brand_name'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label for="model_name" class="form-label-modern">รุ่น</label>
                <input
                    type="text"
                    name="model_name"
                    id="model_name"
                    class="form-control form-control-modern"
                    value="<?= htmlspecialchars($devices['model_name'] ?? '') ?>">
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
                    value="<?= htmlspecialchars($devices['serial_number'] ?? '') ?>"
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
                        <?= (($devices['device_type_id'] ?? null) == $type['device_type_id']) ? 'selected' : '' ?>>
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
                        <?= (($devices['svp_department_id'] ?? null) == $dept['svp_department_id']) ? 'selected' : '' ?>>
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
                        <?= (($devices['svp_user_id'] ?? null) == $user['svp_user_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if ($isEdit): ?>
        <div class="form-section" style="border-bottom: none; margin-bottom: 0.5rem; padding-bottom: 0;">
            <label class="switch-modern">
                <input type="checkbox" name="is_active" id="is_active" value="1" <?= !empty($devices['is_active']) ? 'checked' : '' ?>>
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
</form>

<style>
    .form-card {
        background: #fff;
        border-radius: 14px;
        border: 1px solid #eef0f2;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        padding: 1.75rem;
    }

    .form-section {
        margin-bottom: 1.75rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #f1f2f4;
    }

    .section-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #4f7cff;
        margin-bottom: 1rem;
    }

    .form-label-modern {
        font-size: 0.85rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.35rem;
        display: block;
    }

    .form-control-modern {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.55rem 0.85rem;
        font-size: 0.9rem;
        transition: all 0.15s ease;
    }

    .form-control-modern:focus {
        border-color: #4f7cff;
        box-shadow: 0 0 0 3px rgba(79, 124, 255, 0.15);
    }

    .input-group-modern {
        position: relative;
    }

    .input-group-modern .input-icon {
        position: absolute;
        left: 0.85rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 0.9rem;
        pointer-events: none;
    }

    .input-group-modern .form-control-modern {
        padding-left: 2.3rem;
    }

    .switch-modern {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        cursor: pointer;
        user-select: none;
    }

    .switch-modern input {
        display: none;
    }

    .switch-track {
        width: 40px;
        height: 22px;
        background: #d1d5db;
        border-radius: 999px;
        position: relative;
        transition: background 0.2s ease;
        flex-shrink: 0;
    }

    .switch-track::after {
        content: "";
        position: absolute;
        width: 18px;
        height: 18px;
        background: #fff;
        border-radius: 50%;
        top: 2px;
        left: 2px;
        transition: transform 0.2s ease;
    }

    .switch-modern input:checked+.switch-track {
        background: #4f7cff;
    }

    .switch-modern input:checked+.switch-track::after {
        transform: translateX(18px);
    }

    .switch-text {
        font-size: 0.88rem;
        color: #374151;
        font-weight: 500;
    }

    .btn-primary-modern {
        background: linear-gradient(135deg, #4f7cff, #2b6cb0);
        border: none;
        color: #fff;
        border-radius: 8px;
        padding: 0.55rem 1.3rem;
        font-weight: 500;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.2s ease;
    }

    .btn-primary-modern:hover {
        box-shadow: 0 4px 10px rgba(43, 108, 176, 0.3);
        color: #fff;
    }

    .btn-secondary-modern {
        background: #f3f4f6;
        border: none;
        color: #4b5563;
        border-radius: 8px;
        padding: 0.55rem 1.3rem;
        font-weight: 500;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-secondary-modern:hover {
        background: #e5e7eb;
        color: #374151;
    }

    .alert-modern {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        font-size: 0.88rem;
        margin-bottom: 1.25rem;
    }

    .alert-danger-modern {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
</style>