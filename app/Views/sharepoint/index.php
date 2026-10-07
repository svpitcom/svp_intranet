<?php $esc = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div><h1 class="h3 mb-1">SharePoint</h1><p class="text-muted mb-0">เอกสารกลางและรายงาน PM ของโรงงาน</p></div>
    <a class="btn btn-outline-primary" href="<?= APP_URL ?>/sharepoint">ตรวจสอบการเชื่อมต่อ / รีเฟรช</a>
</div>
<?php if ($missing): ?>
    <div class="alert alert-warning"><strong>ยังไม่ได้ตั้งค่าการเชื่อมต่อ</strong><p class="mb-1">ให้ผู้ดูแลเพิ่มค่าต่อไปนี้ในไฟล์ .env ของเซิร์ฟเวอร์:</p>
        <ul class="mb-0"><?php foreach ($missing as $key): ?><li><code><?= $esc($key) ?></code></li><?php endforeach; ?></ul>
    </div>
<?php elseif ($error): ?>
    <div class="alert alert-danger" role="alert"><?= $esc($error) ?></div>
<?php else: ?>
    <div class="alert alert-success">เชื่อมต่อสำเร็จ: <?= $esc($site['displayName'] ?? '') ?></div>
<?php endif; ?>
<div class="card mb-4"><div class="card-body">
    <h2 class="h5">ส่งออกข้อมูลทะเบียนและแผน PM</h2>
    <p class="small text-muted">ทะเบียนและประเภทอุปกรณ์ส่งเข้า IT เสมอ ส่วนแผนกและแผน PM ใช้โฟลเดอร์ที่เลือก</p>
    <p>ส่งข้อมูลทั้งหมด รวมรายการที่ปิดใช้งาน เป็น CSV ภาษาไทยแยกประเภท เปิดด้วย Excel ได้ ไฟล์จะอยู่ในโฟลเดอร์ปลายทางที่เลือกบน SharePoint</p>
    <form method="post" action="<?= APP_URL ?>/sharepoint/export-tables" class="d-flex flex-wrap gap-2">
        <?php require BASE_PATH . '/app/Views/partials/sharepoint_destination.php'; ?>
        <input type="hidden" name="_csrf" value="<?= $esc(Session::csrfToken()) ?>">
        <?php foreach (SharePointTables::LABELS as $type => $label): ?>
            <button name="type" value="<?= $esc($type) ?>" class="btn btn-outline-primary" <?= !$canExport ? 'disabled' : '' ?>><?= $esc($label) ?></button>
        <?php endforeach; ?>
        <button name="type" value="all" class="btn btn-primary" <?= !$canExport ? 'disabled' : '' ?>>ส่งทั้ง 4 ประเภท</button>
    </form>
    <p class="small text-muted mt-2 mb-0">แต่ละครั้งสร้างไฟล์ใหม่ ณ เวลาที่ส่งออก ข้อมูลหลักยังอยู่ใน Intranet และไม่ได้ซิงก์อัตโนมัติ</p>
</div></div>
<div class="card mb-4"><div class="card-body">
    <h2 class="h5">ส่งออกประวัติ PM</h2>
    <p>สร้างไฟล์ CSV ใหม่จากประวัติ PM ทั้งหมด เปิดด้วย Excel ได้ โดยไม่รวมไฟล์แนบ แต่ละครั้งเป็นสำเนาข้อมูล ณ เวลาที่ส่งออก</p>
    <form method="post" action="<?= APP_URL ?>/sharepoint/export">
        <?php require BASE_PATH . '/app/Views/partials/sharepoint_destination.php'; ?>
        <input type="hidden" name="_csrf" value="<?= $esc(Session::csrfToken()) ?>">
        <button class="btn btn-primary" <?= !$canExport ? 'disabled' : '' ?>>ส่งประวัติ PM ไป SharePoint</button>
    </form>
    <?php if (!$canExport): ?><p class="text-muted mt-2 mb-0">ต้องตั้งค่าการเชื่อมต่อและ SHAREPOINT_EXPORT_FOLDER_ID ก่อนใช้งาน</p><?php endif; ?>
</div></div>
<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between mb-3"><h2 class="h5">ไฟล์ใน Document Library</h2>
        <?php if ($folder !== ''): ?><a href="<?= APP_URL ?>/sharepoint">กลับโฟลเดอร์หลัก</a><?php endif; ?></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>ชื่อ</th><th>ประเภท</th><th>แก้ไขล่าสุด</th><th>เปิด</th></tr></thead><tbody>
    <?php foreach ($page['items'] as $item):
        $isFolder = isset($item['folder']);
        $webUrl = $item['webUrl'] ?? '';
        $safeUrl = is_string($webUrl) && filter_var($webUrl, FILTER_VALIDATE_URL) && strtolower(parse_url($webUrl, PHP_URL_SCHEME) ?? '') === 'https' && !parse_url($webUrl, PHP_URL_USER);
    ?>
        <tr><td><?= $esc($item['name'] ?? '') ?></td><td><?= $isFolder ? 'โฟลเดอร์' : 'ไฟล์' ?></td><td><?= $esc($item['lastModifiedDateTime'] ?? '') ?></td><td>
        <?php if ($isFolder): ?><a href="<?= APP_URL ?>/sharepoint?folder=<?= rawurlencode($item['id']) ?>">เปิดโฟลเดอร์</a>
        <?php elseif ($safeUrl): ?><a href="<?= $esc($webUrl) ?>" target="_blank" rel="noopener noreferrer">เปิดใน SharePoint</a><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (!$page['items']): ?><tr><td colspan="4" class="text-muted text-center"><?= $missing || $error ? 'รายการไฟล์ยังไม่พร้อมแสดง' : 'ไม่มีไฟล์ในโฟลเดอร์นี้' ?></td></tr><?php endif; ?>
    </tbody></table></div>
    <?php if ($page['cursor'] !== ''): ?><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/sharepoint?<?= $esc(http_build_query(['folder' => $folder, 'cursor' => $page['cursor']])) ?>">หน้าถัดไป</a><?php endif; ?>
    <p class="small text-muted mb-0">การเปิดไฟล์ต้องเข้าสู่ระบบ Microsoft 365 และมีสิทธิ์เข้าถึงไฟล์นั้น</p>
</div></div>
