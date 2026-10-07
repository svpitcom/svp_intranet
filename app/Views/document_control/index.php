<?php $esc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div><span class="text-muted small">DCC · DOCUMENT CONTROL</span>
        <h1 class="h3 fw-bold mt-2 mb-1">ทะเบียนควบคุมเอกสาร</h1>
        <p class="text-muted mb-0">จัดการฉบับปัจจุบัน ติดตาม Revision และประวัติการแก้ไข</p>
    </div>
    <a class="btn-primary-modern" href="<?= APP_URL ?>/document-control/create"><i class="bi bi-plus-lg"></i> ลงทะเบียนเอกสาร</a>
</div>
<div class="row g-3 mb-4">
    <?php foreach (ControlledDocument::STATUSES as $key => $label): ?>
        <div class="col-12 col-sm-4"><a class="card-modern p-3 d-block text-decoration-none text-body" href="<?= APP_URL ?>/document-control?status=<?= $esc($key) ?>"><span class="small text-muted"><?= $label ?></span><strong class="d-block fs-2 mt-1"><?= number_format($counts[$key]) ?></strong></a></div>
    <?php endforeach; ?>
</div>
<div class="d-flex flex-wrap gap-2 mb-3"><a class="btn btn-primary" href="<?= APP_URL ?>/document-control">ทะเบียนเอกสาร</a><a class="btn btn-outline-primary" href="<?= APP_URL ?>/document-control/files"><i class="bi bi-cloud"></i> ไฟล์ใน SharePoint DCC</a></div>
<form class="d-flex flex-wrap gap-2 mb-3" method="get" action="<?= APP_URL ?>/document-control">
    <input class="form-control flex-grow-1" style="max-width:440px" name="q" value="<?= $esc($q) ?>" placeholder="ค้นหารหัสหรือชื่อเอกสาร" aria-label="ค้นหาเอกสาร">
    <select class="form-select" style="max-width:190px" name="status" aria-label="สถานะ">
        <option value="">ทุกสถานะ</option><?php foreach (ControlledDocument::STATUSES as $key => $label): ?><option value="<?= $key ?>" <?= $status === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">ค้นหา</button><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/document-control">ล้างตัวกรอง</a>
</form>
<div class="card-modern">
    <div class="p-3 border-bottom text-muted small">พบ <?= number_format($total) ?> เอกสาร · ประวัติในระบบบันทึกการแก้ทะเบียน ส่วนเวอร์ชันไฟล์ดูใน SharePoint</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>รหัส / ชื่อเอกสาร</th>
                    <th>Revision</th>
                    <th>แผนก</th>
                    <th>สถานะ</th>
                    <th>วันที่มีผล</th>
                    <th>ทบทวนครั้งถัดไป</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?><tr>
                        <td><strong><?= $esc($row['code']) ?></strong>
                            <div class="text-muted"><?= $esc($row['title']) ?></div>
                        </td>
                        <td><span class="code-chip"><?= $esc($row['revision']) ?></span></td>
                        <td><?= $esc($row['svp_department_name'] ?? '—') ?></td>
                        <td><span class="badge <?= $row['status'] === 'active' ? 'bg-success' : ($row['status'] === 'obsolete' ? 'bg-secondary' : 'bg-warning text-dark') ?>"><?= $esc(ControlledDocument::STATUSES[$row['status']] ?? $row['status']) ?></span></td>
                        <td><?= $esc($row['effective_date'] ?? '—') ?></td>
                        <td><?= $esc($row['review_date'] ?? '—') ?></td>
                        <td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/document-control/<?= (int)$row['id'] ?>/edit">รายละเอียด / แก้ไข</a>
                            <?php if (DocumentControlInput::safeUrl($row['file_url'])): ?><a class="btn btn-sm btn-outline-secondary" href="<?= $esc($row['file_url']) ?>" target="_blank" rel="noopener noreferrer">เปิดไฟล์</a><?php endif; ?></td>
                    </tr><?php endforeach; ?>
                <?php if (!$rows): ?><tr>
                        <td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-files fs-2 d-block mb-2"></i>ยังไม่มีเอกสารตามเงื่อนไขนี้<br>เริ่มจากลงทะเบียน หรือเลือกไฟล์ใน SharePoint DCC</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="p-3 d-flex justify-content-between align-items-center"><span class="small text-muted">หน้า <?= $page ?> / <?= $pages ?></span>
        <div><?php if ($page > 1): ?><a class="btn btn-sm btn-outline-secondary" href="?<?= $esc(http_build_query(['q' => $q, 'status' => $status, 'page' => $page - 1])) ?>">ก่อนหน้า</a><?php endif; ?> <?php if ($page < $pages): ?><a class="btn btn-sm btn-outline-secondary" href="?<?= $esc(http_build_query(['q' => $q, 'status' => $status, 'page' => $page + 1])) ?>">ถัดไป</a><?php endif; ?></div>
    </div>
</div>