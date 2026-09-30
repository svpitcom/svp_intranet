<?php
$isEdit = isset($positions);
$currentUser = Session::get('user');
$errors = Session::get('errors');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1><?= $isEdit ? 'แก้ไขตำแหน่ง' : 'เพิ่มตำแหน่ง' ?></h1>
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
            action="<?= APP_URL ?><?= $isEdit ? '/positions/' . $positions['svp_position_id'] . '/edit' : '/positions/create' ?>">
            <div class="mb-3">
                <label for="position_name" class="form-label">ชื่อตำแหน่ง</label>
                <input
                    type="text"
                    name="position_name"
                    id="position_name"
                    class="form-control"
                    value="<?= htmlspecialchars($positions['position_name'] ?? '') ?>"
                    required
                    autofocus>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มตำแหน่ง' ?>
                </button>
                <a href="<?= APP_URL ?>/positions" class="btn btn-outline-secondary">ยกเลิก</a>
            </div>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        </form>
    </div>
</div>
