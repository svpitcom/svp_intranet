<div class="d-flex justify-content-between align-items-start gap-3 mb-4 flex-wrap">
    <div><h1 class="h3">KPI / ผลการดำเนินงาน</h1><p class="text-muted">เป้าหมายรายเดือน · รายแผนกและรายบุคคล</p></div>
    <?php if (Portal::manager($user)): ?><a class="btn btn-primary" href="<?= APP_URL ?>/kpi/create">+ กำหนด KPI</a><?php endif; ?>
</div>
<form method="get" class="d-flex gap-2 align-items-end mb-4 flex-wrap">
    <div><label class="form-label" for="kpi-month">เดือนประเมิน (ค.ศ.)</label><input class="form-control" id="kpi-month" type="month" name="period" value="<?= Portal::esc($period) ?>" required></div>
    <button class="btn btn-primary">แสดงผล</button>
</form>
<div class="alert alert-info">คะแนนสูงสุด 100 ต่อหัวข้อ · คะแนนถ่วงน้ำหนักจะแยกแต่ละแผนก/บุคคล และคิดเฉพาะหัวข้อที่มีผลแล้ว หากข้อมูลยังไม่ครบจะแสดงเป็นคะแนนชั่วคราว ไม่ใช่ผลประเมินที่อนุมัติแล้ว</div>
<div class="row g-3 mb-4">
<?php foreach ($summaries as $s): ?>
    <div class="col-12 col-md-6 col-xl-4"><div class="card h-100"><div class="card-body">
        <h2 class="h6"><?= Portal::esc($s['label']) ?></h2>
        <div class="fs-2 fw-bold text-primary"><?= $s['weight']>0?number_format($s['weighted']/$s['weight'],2):'—' ?><span class="fs-6 text-muted"> / 100</span></div>
        <div class="small text-muted">มีผล <?= (int)$s['filled'] ?> / <?= (int)$s['total'] ?> หัวข้อ <?= $s['filled']<$s['total']?'· คะแนนชั่วคราว':'' ?></div>
    </div></div></div>
<?php endforeach; ?>
</div>
<?php if (!$rows): ?><div class="card"><div class="card-body text-center py-5"><h2 class="h5">ยังไม่มี KPI ในเดือนนี้</h2><p class="text-muted mb-0">ให้ผู้จัดการแผนกหรือผู้ดูแลระบบกำหนดหัวข้อ เป้าหมาย และน้ำหนักก่อนเริ่มบันทึกผล</p></div></div><?php endif; ?>
<?php foreach ($rows as $row): ?>
<section class="card mb-3"><div class="card-body">
    <div class="d-flex justify-content-between gap-3 flex-wrap">
        <div><h2 class="h5"><?= Portal::esc($row['title']) ?></h2>
            <p class="small text-muted mb-1"><?= Portal::esc($row['svp_department_name']) ?> · <?= $row['user_id']?Portal::esc($row['first_name'].' '.$row['last_name']):'ระดับแผนก' ?></p>
            <span class="badge text-bg-light"><?= Portal::esc(Kpi::SOURCES[$row['source']]) ?></span>
        </div>
        <div class="text-end"><strong class="fs-3 text-primary"><?= $row['score']===null?'รอข้อมูล':number_format($row['score'],2) ?></strong><div class="small text-muted">คะแนน / 100 · น้ำหนัก <?= Portal::esc($row['weight']+0) ?></div></div>
    </div>
    <div class="row g-3 mt-1 mb-3">
        <div class="col-6 col-md-4"><small class="text-muted">เป้าหมาย</small><div class="fw-bold"><?= number_format((float)$row['target'],2) ?> <?= Portal::esc($row['unit']) ?></div></div>
        <div class="col-6 col-md-4"><small class="text-muted">ผลงานจริง</small><div class="fw-bold"><?= $row['actual']===null?'—':number_format((float)$row['actual'],2) ?> <?= Portal::esc($row['unit']) ?></div></div>
        <div class="col-12 col-md-4"><small class="text-muted">เกณฑ์</small><div><?= $row['direction']==='higher'?'ยิ่งมากยิ่งดี':'ยิ่งน้อยยิ่งดี' ?></div></div>
    </div>
    <?php if ($row['source']==='manual'): ?>
        <?php if (KpiPolicy::writes($user,$row) && $period<=substr(Portal::today(),0,7)): ?>
        <form method="post" action="<?= APP_URL ?>/kpi/<?= (int)$row['id'] ?>/result" class="row g-2 align-items-end">
            <input type="hidden" name="_csrf" value="<?= Portal::esc(Session::csrfToken()) ?>"><input type="hidden" name="version" value="<?= (int)($row['version']??0) ?>">
            <div class="col-12 col-md-3"><label class="form-label" for="actual-<?= (int)$row['id'] ?>">ผลงานจริง</label><input class="form-control" id="actual-<?= (int)$row['id'] ?>" name="actual" type="number" min="0" max="9999999999.9999" step="0.0001" value="<?= Portal::esc($row['actual']??'') ?>" required></div>
            <div class="col-12 col-md-6"><label class="form-label" for="note-<?= (int)$row['id'] ?>">หมายเหตุ / ที่มาข้อมูล</label><textarea class="form-control" id="note-<?= (int)$row['id'] ?>" name="note" rows="1" maxlength="4000"><?= Portal::esc($row['note']??'') ?></textarea></div>
            <div class="col-12 col-md-3"><button class="btn btn-primary">บันทึกผลงาน</button></div>
        </form>
        <?php elseif (!empty($row['note'])): ?><p class="small"><?= nl2br(Portal::esc($row['note'])) ?></p><?php endif; ?>
        <a class="small d-inline-block mt-3" href="<?= APP_URL ?>/kpi/<?= (int)$row['id'] ?>/history">ประวัติการบันทึก</a>
    <?php else: ?><p class="small text-muted mb-0">คำนวณจากข้อมูลปัจจุบัน ณ เวลาเปิดหน้านี้ ไม่ใช่คะแนนที่ล็อกยอดแล้ว</p><?php endif; ?>
</div></section>
<?php endforeach; ?>
<details class="card mt-4"><summary class="card-header">สูตรคะแนนและขอบเขตข้อมูลอัตโนมัติ</summary><div class="card-body small">
    <ul><li>ยิ่งมากยิ่งดี: ผลจริง ÷ เป้าหมาย × 100; ยิ่งน้อยยิ่งดี: เป้าหมาย ÷ ผลจริง × 100 (ผลจริงเป็น 0 ได้ 100) จำกัดคะแนนไม่เกิน 100</li>
    <li>คะแนนรวม: ผลรวม (คะแนน × น้ำหนัก) ÷ ผลรวมน้ำหนักของหัวข้อที่มีผล ไม่จำเป็นต้องรวมน้ำหนักเป็น 100</li>
    <li>PM นับตามวันที่ทำและผู้ทำ PM; สัดส่วนสำเร็จ = บันทึก completed ÷ บันทึก PM ทั้งหมด ไม่ใช่ % ทำตรงเวลาและไม่ใช่จำนวนแผน</li>
    <li>งานซ่อมนับตามเดือนที่มีเหตุการณ์ resolve ครั้งแรกต่อใบงาน และผู้กด resolve; เวลาเฉลี่ยเริ่มจากสร้างใบงานจน resolve ครั้งแรก ไม่ใช่เวลาทำงานจริงหรือเวลาหลังเปิดงานซ้ำ</li>
    <li>แผนกอ้างอิงแผนกปัจจุบันของผู้ทำงาน ไม่ใช่แผนกเจ้าของอุปกรณ์; หากย้ายแผนก แก้ไขหรือลบข้อมูลต้นทาง ผลย้อนหลังอาจเปลี่ยน</li>
    <li>เดือนที่ไม่มีรายการ: ตัวนับเป็น 0 แต่อัตรา/ค่าเฉลี่ยเป็น “รอข้อมูล”; เดือนอนาคตไม่คำนวณผลงาน</li></ul>
</div></details>
