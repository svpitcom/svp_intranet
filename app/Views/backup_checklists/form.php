<?php
$esc = static fn($v) => Portal::esc(is_scalar($v) ? (string)$v : '');
$month = $data['month'];
$dayCount = BackupChecklist::days($month);
$field = static function (string $key, string $label, string $type = 'text', bool $required = false) use ($data, $esc): void { ?>
    <label class="form-label w-100"><?= $esc($label) ?><input class="form-control" type="<?= $type ?>" name="<?= $key ?>" value="<?= $esc($data[$key] ?? '') ?>" <?= $required ? 'required' : '' ?> <?= $type === 'text' ? 'maxlength="120"' : 'min="2000-01-01" max="2099-12-31"' ?>></label>
<?php }; ?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/backup-checklist.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/backup-checklist.css') ?>">
<div class="d-flex justify-content-between flex-wrap gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">ตรวจ Backup Server</h1>
        <p class="text-muted mb-0">IT-F-004 Rev.00 · ประจำเดือน <?= $esc($month) ?></p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-start"><a class="btn btn-outline-secondary" href="<?= APP_URL ?>/it-backup">รายการแบบฟอร์ม</a><?php if ($row): ?><a class="btn btn-success" href="<?= APP_URL ?>/it-backup/<?= (int)$row['id'] ?>/export?format=xlsx">Export Excel</a><a class="btn btn-outline-danger" href="<?= APP_URL ?>/it-backup/<?= (int)$row['id'] ?>/export?format=pdf">Export PDF</a><?php endif; ?></div>
</div>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= $esc($error) ?><?php if ($row): ?> <a class="alert-link" href="<?= APP_URL ?>/it-backup/<?= (int)$row['id'] ?>">เปิดฉบับล่าสุด</a><?php endif; ?></div><?php endif; ?>
<form id="backup-checklist-form" method="post" action="<?= APP_URL ?>/it-backup/<?= $row ? (int)$row['id'] : 'create' ?>">
    <input type="hidden" name="_csrf" value="<?= $esc(Session::csrfToken()) ?>"><input type="hidden" name="version" value="<?= $esc($data['version'] ?? 0) ?>"><input type="hidden" name="month" value="<?= $esc($month) ?>">
    <section class="card mb-3">
        <div class="card-body">
            <h2 class="h5 mb-3"><?= BackupChecklist::TITLE ?></h2>
            <div class="row g-3">
                <div class="col-md-4"><?php $field('location', 'Location', required: true); ?></div>
                <div class="col-md-8"><?php $field('server_name', 'Server', required: true); ?></div>
            </div>
            <p class="small text-muted mb-0">เดือน <?= $esc($month) ?> มี <?= $dayCount ?> วัน · ช่องว่างหมายถึงยังไม่ได้บันทึกผลตรวจ</p>
        </div>
    </section>
    <section class="card mb-3">
        <div class="card-body">
            <h2 class="h5">FILE to cloud_HOUR</h2>
            <p class="text-muted small">Incremental 1 (Daily) · กรอกเวลาเริ่ม–จบตามผลจริง เวลาจบก่อนเวลาเริ่มใช้สำหรับงานที่ข้ามเที่ยงคืน</p>
            <div class="table-responsive backup-grid-scroll" role="region" aria-label="ตารางตรวจ Backup รายวัน เลื่อนแนวนอนเพื่อกรอกทุกช่อง" tabindex="0">
                <table class="table table-bordered align-middle backup-grid">
                    <caption class="visually-hidden">ผลตรวจ Backup Server ประจำเดือน <?= $esc($month) ?></caption>
                    <thead>
                        <tr>
                            <th scope="col" rowspan="2">Date</th>
                            <th scope="col" rowspan="2">Data</th>
                            <th scope="col" rowspan="2">Data Full</th>
                            <th scope="colgroup" colspan="3">Incremental 1 (Daily)</th>
                            <th scope="col" rowspan="2">ผู้ตรวจเช็ก</th>
                            <th scope="col" rowspan="2">หมายเหตุ</th>
                        </tr>
                        <tr>
                            <th scope="col">Start</th>
                            <th scope="col">End</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($day = 1; $day <= $dayCount; $day++): $entry = is_array($data['days'][$day] ?? null) ? $data['days'][$day] : [];
                            $date = $month . '-' . sprintf('%02d', $day); ?>
                            <tr>
                                <th scope="row"><time datetime="<?= $date ?>"><?= $day ?>/<?= (int)substr($month, 5) ?></time><span class="d-block small text-muted"><?= substr($month, 0, 4) ?></span></th>
                                <?php foreach (BackupChecklist::ROW_FIELDS as $key => $label): ?><td><?php if ($key === 'notes'): ?><textarea class="form-control" rows="2" maxlength="500" name="days[<?= $day ?>][notes]" aria-label="หมายเหตุ วันที่ <?= $date ?>"><?= $esc($entry[$key] ?? '') ?></textarea><?php else: ?><input class="form-control" type="<?= in_array($key, ['start', 'end'], true) ? 'time' : 'text' ?>" <?= in_array($key, ['start', 'end'], true) ? 'step="1"' : 'maxlength="120"' ?> <?= $key === 'status' ? 'list="backup-status-suggestions"' : '' ?> name="days[<?= $day ?>][<?= $key ?>]" value="<?= $esc($entry[$key] ?? '') ?>" aria-label="<?= $esc($label) ?> วันที่ <?= $date ?>"><?php endif; ?></td><?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div><datalist id="backup-status-suggestions">
                <option value="Success">
                <option value="Failed">
                <option value="Warning">
                <option value="Running">
                <option value="N/A">
            </datalist>
            <p class="small text-muted mt-2 mb-0">Data, Data Full และ Status กรอกตามรูปแบบที่หน่วยงานใช้ ไม่คำนวณหรือสรุปผลให้อัตโนมัติ</p>
        </div>
    </section>
    <section class="card mb-3">
        <div class="card-body">
            <h2 class="h5">ผู้จัดทำและผู้อนุมัติในเอกสาร</h2>
            <div class="row g-3">
                <div class="col-md-6"><?php $field('prepared_by', 'Prepare By'); ?></div>
                <div class="col-md-6"><?php $field('approved_by', 'Approve By'); ?></div>
                <div class="col-md-6"><?php $field('prepared_date', 'วันที่จัดทำ', 'date'); ?></div>
                <div class="col-md-6"><?php $field('approved_date', 'วันที่อนุมัติ', 'date'); ?></div>
            </div>
            <p class="small text-muted mb-0">ช่องชื่อและวันที่เป็นข้อมูลในเอกสาร ไม่ใช่การอนุมัติผ่านระบบหรือลายเซ็นดิจิทัล</p>
        </div>
    </section>
    <div class="backup-save-bar">
        <div><span id="backup-save-state" role="status"><?= $row ? 'บันทึกแล้ว · ฉบับที่ ' . (int)$row['version'] : 'แบบฟอร์มใหม่ ยังไม่ได้บันทึก' ?></span>
            <div class="small text-muted">Excel / PDF ใช้ข้อมูลฉบับที่บันทึกแล้ว</div>
        </div><button class="btn btn-primary" type="submit">บันทึกแบบฟอร์ม</button>
    </div>
    <p class="small text-muted text-end mt-3">IT-F-004 Rev.00 Issue date: 17-03-2025</p>
</form>
<script>
    (() => {
        const form = document.getElementById('backup-checklist-form'),
            status = document.getElementById('backup-save-state'),
            initial = new URLSearchParams(new FormData(form)).toString(),
            initialText = status.textContent;
        let dirty = false;
        const changed = () => {
            dirty = new URLSearchParams(new FormData(form)).toString() !== initial;
            status.textContent = dirty ? 'มีการแก้ไขที่ยังไม่ได้บันทึก' : initialText;
        };
        form.addEventListener('input', changed);
        form.addEventListener('change', changed);
        window.addEventListener('beforeunload', event => {
            if (dirty) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
        form.addEventListener('submit', () => {
            dirty = false;
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'กำลังบันทึก…';
        });
        window.addEventListener('pageshow', () => {
            const button = form.querySelector('button[type="submit"]');
            button.disabled = false;
            button.textContent = 'บันทึกแบบฟอร์ม';
            changed();
        });
    })();
</script>