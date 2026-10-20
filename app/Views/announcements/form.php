<h1 class="h3 mb-3"><?= $id ? 'แก้ไขประกาศ' : 'เขียนประกาศ' ?></h1>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= Portal::esc($error) ?></div><?php endif; ?>
<form method="post" action="<?= APP_URL ?>/announcements/<?= $id ? (int)$id . '/edit' : 'create' ?>" class="card p-4" style="max-width:900px">
    <input type="hidden" name="_csrf" value="<?= Portal::esc(Session::csrfToken()) ?>"><input type="hidden" name="version" value="<?= (int)($data['version'] ?? 0) ?>">
    <label for="title" class="form-label">หัวข้อ *</label><input id="title" name="title" maxlength="200" required class="form-control mb-3" value="<?= Portal::esc($data['title'] ?? '') ?>">
    <label for="body" class="form-label">เนื้อหาประกาศ *</label><textarea id="body" name="body" rows="10" maxlength="20000" required class="form-control mb-3"><?= Portal::esc($data['body'] ?? '') ?></textarea>
    <div class="row">
        <div class="col-md-6 mb-3"><label for="department" class="form-label">ผู้รับประกาศ</label><select id="department" name="department_id" class="form-select">
                <option value="">ทุกแผนก</option><?php foreach ($departments as $department): ?><option value="<?= (int)$department['svp_department_id'] ?>" <?= ($data['department_id'] ?? '') == $department['svp_department_id'] ? 'selected' : '' ?>><?= Portal::esc($department['svp_department_name']) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-6 mb-3"><label for="status" class="form-label">สถานะ</label><select id="status" name="status" class="form-select"><?php foreach (Announcement::STATUSES as $key => $label): ?><option value="<?= $key ?>" <?= ($data['status'] ?? 'draft') === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3"><label for="publish" class="form-label">เริ่มเผยแพร่ *</label><input id="publish" type="date" name="publish_date" required class="form-control" value="<?= Portal::esc($data['publish_date'] ?? Portal::today()) ?>"></div>
        <div class="col-md-6 mb-3"><label for="expires" class="form-label">สิ้นสุดการเผยแพร่</label><input id="expires" type="date" name="expires_date" class="form-control" value="<?= Portal::esc($data['expires_date'] ?? '') ?>"></div>
    </div>
    <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="pinned" value="1" <?= !empty($data['pinned']) ? 'checked' : '' ?>> ปักหมุดประกาศ</label>
    <p class="form-text">หากแก้ไขหัวข้อ เนื้อหา หรือผู้รับ ระบบจะให้รับทราบฉบับใหม่อีกครั้ง</p>
    <div class="d-flex gap-2"><button class="btn btn-primary">บันทึกประกาศ</button><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/announcements">กลับรายการ</a></div>
</form>