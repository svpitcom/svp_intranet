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
<input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
</form>
