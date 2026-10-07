<?php $esc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); ?>
<div class="d-flex justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold">ไฟล์ใน SharePoint DCC</h1>
        <p class="text-muted mb-0">เลือกไฟล์เพื่อลงทะเบียนเอกสาร หรือเปิดจัดการไฟล์ใน SharePoint</p>
    </div><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/document-control">กลับทะเบียนเอกสาร</a>
</div>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= $esc($error) ?></div><a class="btn btn-outline-primary" href="<?= APP_URL ?>/document-control/files">ลองเชื่อมต่ออีกครั้ง</a><?php else: ?>
    <div class="card-modern">
        <div class="p-3 d-flex flex-wrap justify-content-between gap-2 border-bottom"><strong><i class="bi bi-folder2-open"></i> <?= $esc($folder['name'] ?? 'DCC') ?></strong>
            <div><a class="btn btn-sm btn-outline-secondary" href="<?= APP_URL ?>/document-control/files?document_id=<?= $documentId ?>">กลับ DCC</a>
                <?php if (DocumentControlInput::safeUrl($folder['webUrl'] ?? '')): ?><a class="btn btn-sm btn-primary" href="<?= $esc($folder['webUrl']) ?>" target="_blank" rel="noopener noreferrer">อัปโหลด / จัดการใน SharePoint</a><?php endif; ?></div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>ชื่อไฟล์ / โฟลเดอร์</th>
                        <th>แก้ไขล่าสุด (UTC)</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?><tr>
                            <td><i class="bi <?= isset($item['folder']) ? 'bi-folder text-warning' : 'bi-file-earmark-text text-primary' ?> me-2"></i><?= $esc($item['name']) ?></td>
                            <td class="small text-muted"><?= $esc($item['lastModifiedDateTime'] ?? '—') ?></td>
                            <td>
                                <?php if (isset($item['folder'])): ?><a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/document-control/files?<?= $esc(http_build_query(['folder' => $item['id'], 'document_id' => $documentId])) ?>">เปิดโฟลเดอร์</a>
                                <?php elseif (isset($item['file'])): ?><a class="btn btn-sm btn-primary" href="<?= APP_URL ?>/document-control/<?= $documentId ? $documentId . '/edit' : 'create' ?>?file=<?= rawurlencode($item['id']) ?>"><?= $documentId ? 'เลือกไฟล์นี้' : 'ลงทะเบียน' ?></a><?php endif; ?>
                                <?php if (DocumentControlInput::safeUrl($item['webUrl'] ?? '')): ?><a class="btn btn-sm btn-outline-secondary" href="<?= $esc($item['webUrl']) ?>" target="_blank" rel="noopener noreferrer">เปิดใน SharePoint</a><?php endif; ?></td>
                        </tr><?php endforeach; ?>
                    <?php if (!$items): ?><tr>
                            <td colspan="3" class="text-muted text-center py-5">ยังไม่มีไฟล์ในโฟลเดอร์นี้ ใช้ปุ่มจัดการใน SharePoint เพื่ออัปโหลด</td>
                        </tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($cursor !== ''): ?><a class="btn btn-outline-primary mt-3" href="<?= APP_URL ?>/document-control/files?<?= $esc(http_build_query(['folder' => $folder['id'], 'cursor' => $cursor, 'document_id' => $documentId])) ?>">ไฟล์หน้าถัดไป</a><?php endif; ?>
<?php endif; ?>