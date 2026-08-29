<?php
$isEdit = isset($department);
$currentUser = Session::get('user');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?= $isEdit ? 'แก้ไขแผนก' : 'เพิ่มแผนก' ?></h1>
</div>

<?php if (Session::get('errors')): ?>
    <div class="alert alert-danger"><?= htmlspecialchars(Session::get('errors')) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST"
            action="<?= APP_URL ?><?= $isEdit ? '/positions/' . $department['svp_department_id'] . '/edit' : '/departments/create' ?>">
            <div class="mb-3">
                <label for="svp_code_department" class="form-label">รหัสแผนก</label>
                <input
                    type="text"
                    name="svp_code_department"
                    id="svp_code_department"
                    class="form-control"
                    value="<?= htmlspecialchars($department['svp_code_department'] ?? '') ?>"
                    required
                    autofocus>
            </div>
            <div class="mb-3">
                <label for="svp_department_name" class="form-label">ชื่อแผนก</label>
                <input
                    type="text"
                    name="svp_department_name"
                    id="svp_department_name"
                    class="form-control"
                    value="<?= htmlspecialchars($department['svp_department_name'] ?? '') ?>"
                    required
                    autofocus>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มแผนก' ?>
                </button>
                <a href="<?= APP_URL ?>/departments" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>