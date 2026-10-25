<div>
    <label class="form-label" for="kpi-department">แผนก</label>
    <?php if (($user['user_role']??'')==='admin'): ?>
    <select class="form-select" id="kpi-department" name="department_id">
        <option value="">ทุกแผนก</option>
        <?php foreach ($departments as $department): ?>
        <option value="<?= (int)$department['svp_department_id'] ?>" <?= (int)($departmentId??0)===(int)$department['svp_department_id']?'selected':'' ?>><?= Portal::esc($department['svp_department_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php else: ?>
    <input type="hidden" name="department_id" value="<?= (int)$departmentId ?>">
    <input class="form-control" id="kpi-department" value="<?= Portal::esc($departments[0]['svp_department_name']??'แผนกของคุณ') ?>" readonly>
    <?php endif; ?>
</div>
