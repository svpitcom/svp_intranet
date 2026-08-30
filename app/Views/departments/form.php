<?php
$isEdit = isset($department);
$currentUser = Session::get('user');
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1"><?= $isEdit ? 'แก้ไขแผนก' : 'เพิ่มแผนกใหม่' ?></h4>
    <span class="text-muted small"><?= $isEdit ? 'แก้ไขข้อมูลแผนกในระบบ' : 'กรอกข้อมูลเพื่อเพิ่มแผนกใหม่' ?></span>
</div>

<?php if (Session::get('errors')): ?>
    <div class="alert-modern alert-danger-modern">
        <i class="bi bi-exclamation-circle-fill"></i>
        <?= htmlspecialchars(Session::get('errors')) ?>
    </div>
<?php endif; ?>

<form method="POST"
    action="<?= APP_URL ?><?= $isEdit ? '/departments/' . $department['svp_department_id'] . '/edit' : '/departments/create' ?>"
    class="form-card" style="max-width: 560px;">

    <div class="form-section" style="border-bottom: none; margin-bottom: 0.5rem; padding-bottom: 0;">
        <div class="section-label"><i class="bi bi-diagram-3-fill"></i> ข้อมูลแผนก</div>

        <div class="mb-3">
            <label for="svp_code_department" class="form-label-modern">รหัสแผนก</label>
            <div class="input-group-modern">
                <span class="input-icon"><i class="bi bi-hash"></i></span>
                <input
                    type="text"
                    name="svp_code_department"
                    id="svp_code_department"
                    class="form-control form-control-modern"
                    value="<?= htmlspecialchars($department['svp_code_department'] ?? '') ?>"
                    required
                    autofocus>
            </div>
        </div>

        <div class="mb-1">
            <label for="svp_department_name" class="form-label-modern">ชื่อแผนก</label>
            <div class="input-group-modern">
                <span class="input-icon"><i class="bi bi-building"></i></span>
                <input
                    type="text"
                    name="svp_department_name"
                    id="svp_department_name"
                    class="form-control form-control-modern"
                    value="<?= htmlspecialchars($department['svp_department_name'] ?? '') ?>"
                    required>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 pt-3">
        <button type="submit" class="btn-primary-modern">
            <i class="bi bi-check-lg"></i> <?= $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มแผนก' ?>
        </button>
        <a href="<?= APP_URL ?>/departments" class="btn-secondary-modern">ยกเลิก</a>
    </div>
</form>

<style>
.form-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #eef0f2;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    padding: 1.75rem;
}

.form-section { margin-bottom: 1.75rem; padding-bottom: 1.5rem; border-bottom: 1px solid #f1f2f4; }

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

.input-group-modern { position: relative; }
.input-group-modern .input-icon {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 0.9rem;
    pointer-events: none;
}
.input-group-modern .form-control-modern { padding-left: 2.3rem; }

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
.btn-primary-modern:hover { box-shadow: 0 4px 10px rgba(43, 108, 176, 0.3); color: #fff; }

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
.btn-secondary-modern:hover { background: #e5e7eb; color: #374151; }

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