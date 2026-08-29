<?php
$isEdit = isset($devicetypes);
$currentUser = Session::get('user');
$errors = Session::get('errors');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?= $isEdit ? 'แก้ไขประเภทอุปกรณ์' : 'เพิ่มประเภทอุปกรณ์' ?></h1>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger">
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
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST"
            action="<?= APP_URL ?><?= $isEdit ? '/device_types/' . $devicetypes['device_type_id'] . '/edit' : '/device_types/create' ?>">
            <div class="mb-3">
                <label for="device_type_name" class="form-label">ชื่อประเภทอุปกรณ์</label>
                <input
                    type="text"
                    name="device_type_name"
                    id="device_type_name"
                    class="form-control"
                    value="<?= htmlspecialchars($deviceTypes['device_type_name'] ?? '') ?>"
                    required
                    autofocus>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มประเภทอุปกรณ์' ?>
                </button>
                <a href="<?= APP_URL ?>/device_types" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
</div>