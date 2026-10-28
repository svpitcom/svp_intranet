<div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
    <div><h1 class="h3">SharePoint · <?= Portal::esc($label) ?></h1><p class="text-muted mb-0">ไฟล์และโฟลเดอร์ภายในพื้นที่แผนกของคุณ</p></div>
    <a href="<?= APP_URL ?>/sharepoint" class="btn btn-outline-primary align-self-start">กลับพื้นที่แผนก / รีเฟรช</a>
</div>
<?php if ($error): ?><div class="alert alert-warning" role="alert"><?= Portal::esc($error) ?></div>
<?php else: ?>
<div class="card">
    <div class="card-body border-bottom d-flex flex-wrap justify-content-between gap-3">
        <strong><?= Portal::esc($page['folder']['name']??$label) ?></strong>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($department==='DCC'): ?><a href="<?= APP_URL ?>/document-control/files" class="btn btn-sm btn-outline-secondary">เลือกไฟล์ลงทะเบียนเอกสาร</a><?php endif; ?>
            <?php if (DocumentControlInput::safeUrl($page['folder']['webUrl']??'')): ?><a href="<?= Portal::esc($page['folder']['webUrl']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">เปิดพื้นที่นี้ใน SharePoint</a><?php endif; ?>
        </div>
    </div>
    <div class="table-responsive"><table class="table align-middle mb-0">
        <thead><tr><th>ชื่อไฟล์ / โฟลเดอร์</th><th>ประเภท</th><th>แก้ไขล่าสุด (UTC)</th><th>เปิด</th></tr></thead>
        <tbody><?php foreach ($page['items'] as $item): ?>
            <tr><td><?= Portal::esc($item['name']??'') ?></td><td><?= isset($item['folder'])?'โฟลเดอร์':'ไฟล์' ?></td><td><?= Portal::esc($item['lastModifiedDateTime']??'—') ?></td><td>
                <?php if (isset($item['folder'])): ?><a href="<?= APP_URL ?>/sharepoint?folder=<?= rawurlencode($item['id']) ?>">เปิดโฟลเดอร์</a>
                <?php elseif (DocumentControlInput::safeUrl($item['webUrl']??'')): ?><a href="<?= Portal::esc($item['webUrl']) ?>" target="_blank" rel="noopener noreferrer">เปิดใน SharePoint</a><?php endif; ?>
            </td></tr>
        <?php endforeach; ?>
        <?php if (!$page['items']): ?><tr><td colspan="4" class="text-center text-muted p-4">ยังไม่มีไฟล์ในโฟลเดอร์นี้</td></tr><?php endif; ?></tbody>
    </table></div>
</div>
<?php if ($page['cursor']!==''): ?><a class="btn btn-outline-primary mt-3" href="<?= APP_URL ?>/sharepoint?<?= Portal::esc(http_build_query(['folder'=>$page['folder']['id'],'cursor'=>$page['cursor']])) ?>">หน้าถัดไป</a><?php endif; ?>
<p class="form-text mt-3">การเปิดหรือจัดการไฟล์ใน Microsoft 365 ใช้บัญชีและสิทธิ์ SharePoint ของคุณ</p>
<?php endif; ?>
