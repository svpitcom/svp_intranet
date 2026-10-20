<?php $labels = ['create' => 'สร้างรายการ', 'assign' => 'มอบหมายงาน', 'start' => 'เริ่มดำเนินการ', 'resolve' => 'บันทึกผลซ่อมเสร็จ', 'close' => 'ยืนยันปิดงาน', 'reopen' => 'ส่งกลับแก้ไข', 'cancel' => 'ยกเลิก', 'approve' => 'อนุมัติ', 'reject' => 'ไม่อนุมัติ', 'comment' => 'เพิ่มข้อความ']; ?>
<div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
    <div>
        <h1 class="h3">#<?= (int)$item['id'] ?> <?= Portal::esc($item['title']) ?></h1><span class="badge bg-primary"><?= Portal::esc(WorkItem::STATUSES[$item['status']]) ?></span>
    </div><a href="<?= APP_URL . ($item['kind'] === 'ticket' ? '/tickets' : '/requests') ?>" class="btn btn-outline-secondary align-self-start">กลับรายการ</a>
</div>
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
            <?php if ($item['attachment_path']): ?><a href="<?= APP_URL ?>/work/<?= (int)$item['id'] ?>/attachment">ดาวน์โหลด <?= Portal::esc($item['attachment_name']) ?></a><?php endif; ?>
            <?php if ($item['resolution']): ?>
                <hr>
                <h3 class="h6">ผลการซ่อมล่าสุด</h3>
                <p style="white-space:pre-wrap"><?= Portal::esc($item['resolution']) ?></p><?php endif; ?>
        </section>
        <section class="card p-4">
            <h2 class="h5">ประวัติการดำเนินการ</h2><?php foreach ($events as $event): ?><article class="border-bottom py-3"><strong><?= Portal::esc($labels[$event['action']] ?? $event['action']) ?></strong><small class="d-block text-muted"><?= Portal::esc($event['created_at'] . ' · ' . ($event['first_name'] ?? '') . ' ' . ($event['last_name'] ?? '')) ?></small>
                    <p class="mb-0" style="white-space:pre-wrap;overflow-wrap:anywhere"><?= Portal::esc($event['body']) ?></p>
                </article><?php endforeach; ?>
        </section>
    </div>
    <div class="col-lg-5">
        <form class="card p-4" method="post" action="<?= APP_URL ?>/work/<?= (int)$item['id'] ?>/action">
            <h2 class="h5">ดำเนินการ</h2><input type="hidden" name="_csrf" value="<?= Portal::esc(Session::csrfToken()) ?>"><input type="hidden" name="version" value="<?= (int)$item['version'] ?>">
            <label for="action" class="form-label">รายการที่ต้องการทำ</label><select id="action" name="action" class="form-select mb-3">
                <option value="comment">เพิ่มข้อความ</option><?php foreach ($actions as $action): ?><option value="<?= $action ?>"><?= $labels[$action] ?></option><?php endforeach; ?>
            </select>
            <?php if (in_array('assign', $actions, true)): ?><label for="assignee" class="form-label">ผู้รับงาน (เมื่อเลือกมอบหมายงาน)</label><select id="assignee" name="assignee_id" class="form-select mb-3">
                    <option value="">เลือกผู้รับงาน</option><?php foreach ($people as $person): ?><option value="<?= (int)$person['svp_user_id'] ?>" <?= $item['assignee_id'] == $person['svp_user_id'] ? 'selected' : '' ?>><?= Portal::esc($person['first_name'] . ' ' . $person['last_name']) ?></option><?php endforeach; ?>
                </select><?php endif; ?>
            <label for="note" class="form-label">รายละเอียด / เหตุผล</label><textarea id="note" name="note" rows="5" maxlength="10000" class="form-control mb-2"></textarea>
            <p class="form-text">ต้องระบุรายละเอียดเมื่อเพิ่มข้อความ ซ่อมเสร็จ ส่งกลับ ไม่อนุมัติ หรือยกเลิก</p><button class="btn btn-primary">บันทึกการดำเนินการ</button>
        </form>
    </div>
</div>