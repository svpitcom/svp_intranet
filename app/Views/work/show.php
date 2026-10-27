<?php $labels = ['create' => 'สร้างรายการ', 'assign' => 'มอบหมายงาน', 'claim' => 'รับงานและเริ่มซ่อม', 'start' => 'เริ่มดำเนินการ', 'resolve' => 'บันทึกผลซ่อมเสร็จ', 'close' => 'ยืนยันปิดงาน', 'reopen' => 'ส่งกลับแก้ไข', 'cancel' => 'ยกเลิก', 'approve' => 'อนุมัติ', 'reject' => 'ไม่อนุมัติ', 'comment' => 'เพิ่มข้อความ', 'save_repair'=>'บันทึกข้อมูลการซ่อม', 'form_snapshot'=>'ข้อมูลแบบฟอร์ม ณ เวลาบันทึก'];
$machineForm=$item['machine_form']??null;
$failedInput=Session::get('work_form_input');
Session::remove('work_form_input');
$formInput=is_array($failedInput) && ($failedInput['id']??0)==(int)$item['id'] ? $failedInput['data'] : [];
?>
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <div>
        <h1 class="h3">#<?= (int)$item['id'] ?> <?= Portal::esc($item['title']) ?></h1><span class="badge bg-primary"><?= Portal::esc(WorkItem::STATUSES[$item['status']]) ?></span>
    </div><a href="<?= APP_URL . ($item['kind'] === 'ticket' ? '/tickets' : '/requests') ?>" class="btn btn-outline-secondary align-self-start">กลับรายการ</a>
</div>
<?php if ($item['kind'] === 'ticket' && in_array($item['status'], ['open', 'in_progress'], true)): ?>
    <div class="alert alert-info" role="status">
        <strong><?= $item['status'] === 'open' ? 'รอรับงาน / เริ่มซ่อม' : 'อยู่ระหว่างดำเนินการซ่อม' ?></strong>
        <p class="mb-0"><?= empty($item['assignee_id']) && $item['category'] === 'machine' ? 'พนักงาน Maintenance สามารถกด “รับงานและเริ่มซ่อม” จากบัญชีของตน หรือให้ผู้จัดการมอบหมายผู้รับงาน' : 'ผู้รับงานหรือผู้จัดการต้องบันทึกขั้นตอนซ่อมจากปุ่มดำเนินการ' ?> เมื่อบันทึกผลซ่อมเสร็จแล้ว ผู้แจ้งจะได้รับการแจ้งเตือนและเห็นปุ่มยืนยันปิดเคส การเพิ่มข้อความว่า “ซ่อมเสร็จ” จะไม่เปลี่ยนสถานะงาน</p>
    </div>
<?php endif; ?>
<?php if ($item['kind'] === 'ticket' && $item['status'] === 'resolved'): ?>
    <section class="alert alert-success" aria-labelledby="repair-completed-title">
        <h2 id="repair-completed-title" class="h5">ซ่อมเสร็จแล้ว รอยืนยันปิดเคส</h2>
        <p>กรุณาตรวจสอบผลการซ่อมและทดลองใช้งาน หากยังพบปัญหา สามารถส่งกลับแก้ไขได้</p>
        <?php if (in_array('close', $actions, true)): ?>
            <form method="post" action="<?= APP_URL ?>/work/<?= (int)$item['id'] ?>/action">
                <input type="hidden" name="_csrf" value="<?= Portal::esc(Session::csrfToken()) ?>">
                <input type="hidden" name="version" value="<?= (int)$item['version'] ?>">
                <?php if ($machineForm): ?>
                    <h3 class="h6">3. รายละเอียดการตรวจรับงาน</h3>
                    <label for="acceptance-result" class="form-label">ผลตรวจรับ *</label><select id="acceptance-result" name="acceptance_result" required class="form-select mb-3"><option value="normal" <?= ($formInput['acceptance_result']??'normal')==='normal'?'selected':'' ?>>กลับสู่สภาพพร้อมใช้งานตามปกติ</option><option value="temporary" <?= ($formInput['acceptance_result']??'')==='temporary'?'selected':'' ?>>พร้อมใช้งานชั่วคราว / ต้องแก้ไขอีกครั้ง</option></select>
                    <label for="inspection-date" class="form-label">วันที่ตรวจรับ *</label><input id="inspection-date" name="inspection_date" type="date" required max="<?= Portal::today() ?>" class="form-control mb-3" value="<?= Portal::esc($formInput['inspection_date']??Portal::today()) ?>">
                    <p>ผู้ตรวจรับ: <?= Portal::esc(trim((Session::get('user')['first_name']??'').' '.(Session::get('user')['last_name']??''))) ?></p>
                    <label for="suggestions" class="form-label">ข้อเสนอแนะ / ปัญหาที่ต้องแก้ไข</label><textarea id="suggestions" name="suggestions" maxlength="5000" rows="3" class="form-control mb-3"><?= Portal::esc($formInput['suggestions']??'') ?></textarea>
                    <p class="form-text">ผลปกติให้ยืนยันปิดเคส ส่วนผลชั่วคราวให้ระบุปัญหาแล้วส่งกลับแก้ไข</p>
                <?php else: ?>
                <label for="confirmation-note" class="form-label">ผลตรวจสอบ / ปัญหาที่ยังพบ (ต้องระบุเมื่อส่งกลับแก้ไข)</label>
                <textarea id="confirmation-note" name="note" rows="2" maxlength="10000" class="form-control mb-3"></textarea>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-2">
                    <button name="action" value="close" class="btn btn-success">รับทราบผลซ่อมและยืนยันปิดเคส</button>
                    <button name="action" value="reopen" class="btn btn-outline-danger">ยังพบปัญหา ส่งกลับแก้ไข</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>
<div class="row g-4">
    <div class="col-lg-7">
        <section class="card p-4 mb-3">
            <h2 class="h5">รายละเอียด</h2>
            <p style="white-space:pre-wrap;overflow-wrap:anywhere"><?= Portal::esc($item['description']) ?></p>
            <dl class="row mb-0">
                <dt class="col-sm-4">ผู้ยื่น</dt>
                <dd class="col-sm-8"><?= Portal::esc(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')) ?></dd>
                <dt class="col-sm-4">ประเภท / ความเร่งด่วน</dt>
                <dd class="col-sm-8"><?= Portal::esc((WorkItem::CATEGORIES[$item['kind']][$item['category']] ?? '') . ' / ' . WorkItem::PRIORITIES[$item['priority']]) ?></dd>
                <dt class="col-sm-4">อุปกรณ์ / สถานที่</dt>
                <dd class="col-sm-8"><?= Portal::esc(($item['svp_device_name'] ?? '—') . ' / ' . ($item['location'] ?: '—')) ?></dd>
                <dt class="col-sm-4">วันที่ต้องการ</dt>
                <dd class="col-sm-8"><?= Portal::esc($item['due_date'] ?? 'ไม่ระบุ') ?></dd>
                <dt class="col-sm-4"><?= $item['kind'] === 'ticket' ? 'ผู้รับงาน' : 'ผู้อนุมัติ' ?></dt>
                <dd class="col-sm-8"><?= Portal::esc(trim(($item[$item['kind'] === 'ticket' ? 'assignee_name' : 'approver_name'] ?? '') . ' ' . ($item[$item['kind'] === 'ticket' ? 'assignee_last' : 'approver_last'] ?? '')) ?: 'ยังไม่มอบหมาย') ?></dd>
            </dl>
            <?php if ($machineForm): ?><h3 class="h6 mt-3">MN-F-001-02 · ข้อมูลแจ้งซ่อม</h3><?php $snapshot=$machineForm['request_data']; require __DIR__.'/machine_snapshot.php'; endif; ?>
            <?php if ($item['attachment_path']): ?>
                <?php if (preg_match('/\.(?:jpg|png|webp)\z/',$item['attachment_path'])): ?><a href="<?= APP_URL ?>/work/<?= (int)$item['id'] ?>/attachment" target="_blank" rel="noopener"><img src="<?= APP_URL ?>/work/<?= (int)$item['id'] ?>/attachment" alt="รูปภาพประกอบใบแจ้งซ่อม #<?= (int)$item['id'] ?>" class="img-fluid rounded border my-3" style="max-height:480px;object-fit:contain" loading="lazy"></a><?php else: ?><a href="<?= APP_URL ?>/work/<?= (int)$item['id'] ?>/attachment">ดาวน์โหลด <?= Portal::esc($item['attachment_name']) ?></a><?php endif; ?>
            <?php endif; ?>
            <?php if ($item['resolution']): ?>
                <hr>
                <h3 class="h6">ผลการซ่อมล่าสุด</h3>
                <p style="white-space:pre-wrap"><?= Portal::esc($item['resolution']) ?></p><?php endif; ?>
        </section>
        <?php if ($machineForm): require __DIR__.'/machine_repair.php'; endif; ?>
        <section class="card p-4">
            <h2 class="h5">ประวัติการดำเนินการ</h2><?php foreach ($events as $event): ?><article class="border-bottom py-3"><strong><?= Portal::esc($labels[$event['action']] ?? $event['action']) ?></strong><small class="d-block text-muted"><?= Portal::esc($event['created_at'] . ' · ' . ($event['first_name'] ?? '') . ' ' . ($event['last_name'] ?? '')) ?></small>
                    <?php if ($event['action']==='form_snapshot'): ?><details><summary>ดูข้อมูลแบบฟอร์มที่บันทึก</summary><?php foreach ((json_decode($event['body'],true)??[]) as $snapshot): require __DIR__.'/machine_snapshot.php'; endforeach; ?></details><?php else: ?><p class="mb-0" style="white-space:pre-wrap;overflow-wrap:anywhere"><?= Portal::esc($event['body']) ?></p><?php endif; ?>
                </article><?php endforeach; ?>
        </section>
    </div>
    <div class="col-lg-5">
        <form class="card p-4" method="post" action="<?= APP_URL ?>/work/<?= (int)$item['id'] ?>/action">
            <h2 class="h5">ดำเนินการ</h2>
            <?php if (in_array('resolve', $actions, true)): ?><p class="text-muted"><?= $machineForm?'กรอกส่วนที่ 2 ด้านล่างรายละเอียดใบงาน แล้วกดซ่อมเสร็จเพื่อแจ้งผู้แจ้งตรวจรับ':'เมื่อบันทึกซ่อมเสร็จ ระบบจะแจ้งเตือนผู้แจ้งให้ตรวจสอบและยืนยันปิดเคส' ?></p><?php endif; ?>
            <input type="hidden" name="_csrf" value="<?= Portal::esc(Session::csrfToken()) ?>"><input type="hidden" name="version" value="<?= (int)$item['version'] ?>">
            <?php if ($item['kind'] !== 'ticket'): ?>
            <label for="action" class="form-label">รายการที่ต้องการทำ</label><select id="action" name="action" class="form-select mb-3">
                <option value="comment">เพิ่มข้อความ</option><?php foreach ($actions as $action): ?><option value="<?= $action ?>"><?= $labels[$action] ?></option><?php endforeach; ?>
            </select>
            <?php endif; ?>
            <?php if (in_array('assign', $actions, true)): ?><label for="assignee" class="form-label">ผู้รับงาน (เมื่อเลือกมอบหมายงาน)</label><select id="assignee" name="assignee_id" class="form-select mb-3">
                    <option value="">เลือกผู้รับงาน</option><?php foreach ($people as $person): ?><option value="<?= (int)$person['svp_user_id'] ?>" <?= $item['assignee_id'] == $person['svp_user_id'] ? 'selected' : '' ?>><?= Portal::esc($person['first_name'] . ' ' . $person['last_name']) ?></option><?php endforeach; ?>
                </select>
                <?php if ($item['category']==='machine'): ?><p class="form-text">เลือกได้เฉพาะพนักงานแผนก Maintenance ที่ยังใช้งานอยู่<?= !$people?' — ยังไม่มีพนักงานที่เลือกได้':'' ?></p><?php endif; ?>
            <?php endif; ?>
            <label for="note" class="form-label">รายละเอียด / เหตุผล</label><textarea id="note" name="note" rows="5" maxlength="10000" class="form-control mb-2"></textarea>
            <p class="form-text">ต้องระบุรายละเอียดเมื่อเพิ่มข้อความ ซ่อมเสร็จ ส่งกลับ ไม่อนุมัติ หรือยกเลิก</p>
            <?php if ($item['kind'] === 'ticket'): ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($actions as $action): if (in_array($action, ['close', 'reopen','save_repair'], true) || ($machineForm && $action==='resolve')) continue; ?>
                        <button type="submit" name="action" value="<?= $action ?>" class="btn <?= $action === 'cancel' ? 'btn-outline-danger' : 'btn-primary' ?>"><?= $action === 'resolve' ? 'ซ่อมเสร็จ / แจ้งผู้แจ้งตรวจรับ' : $labels[$action] ?></button>
                    <?php endforeach; ?>
                    <button type="submit" name="action" value="comment" class="btn btn-outline-secondary">เพิ่มข้อความเท่านั้น</button>
                </div>
            <?php else: ?><button class="btn btn-primary">บันทึกการดำเนินการ</button><?php endif; ?>
        </form>
    </div>
</div>
