<?php $destinationConfig = require BASE_PATH . '/app/Config/sharepoint.php'; ?>
<label class="d-inline-block me-2 mb-2">
    โฟลเดอร์ปลายทาง
    <select name="department_target" class="form-select form-select-sm">
        <option value="">ปลายทางเดิม</option>
        <?php foreach ($destinationConfig['department_folders'] as $code => $folderId): ?>
            <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
    </select>
</label>
<span class="small text-muted">เลือกที่เก็บไฟล์ ข้อมูลส่งออกยังรวมทุกแผนก; นำเข้าต้องเลือกโฟลเดอร์เดียวกับไฟล์ที่แก้</span>
