<h1 class="h3 mb-3">กำหนด KPI แผนกรายเดือน</h1>
<p class="text-muted">หัวข้อและเป้าหมายจะเก็บแยกตามเดือน หลังสร้างยังไม่เปิดให้แก้ไขหรือลบเป้าหมาย เพื่อป้องกันการเปลี่ยนเกณฑ์ย้อนหลัง</p>
<form method="post" action="<?= APP_URL ?>/kpi/create" class="card"><div class="card-body">
    <input type="hidden" name="_csrf" value="<?= Portal::esc(Session::csrfToken()) ?>">
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label" for="period">เดือน (ค.ศ.)</label><input class="form-control" type="month" name="period" id="period" min="2000-01" max="2099-12" value="<?= Portal::esc($data['period']??substr(Portal::today(),0,7)) ?>" required></div>
        <div class="col-md-8"><label class="form-label" for="title">หัวข้อ KPI</label><input class="form-control" name="title" id="title" maxlength="200" value="<?= Portal::esc($data['title']??'') ?>" required></div>
        <div class="col-md-6"><label class="form-label" for="department">แผนก</label><select class="form-select" name="department_id" id="department" required><option value="">เลือกแผนก</option><?php foreach ($departments as $d): ?><option value="<?= (int)$d['svp_department_id'] ?>" <?= (string)($data['department_id']??'')===(string)$d['svp_department_id']?'selected':'' ?>><?= Portal::esc($d['svp_department_name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6 d-flex align-items-end"><p class="text-muted mb-2">เป็น KPI ร่วมของแผนก พนักงานในแผนกสามารถกรอกผลได้ทุกคน</p></div>
        <div class="col-md-6"><label class="form-label" for="source">แหล่งข้อมูล</label><select class="form-select" name="source" id="source"><?php foreach (Kpi::SOURCES as $key=>$label): ?><option value="<?= $key ?>" <?= ($data['source']??'manual')===$key?'selected':'' ?>><?= Portal::esc($label) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label" for="direction">วิธีคิดคะแนน</label><select class="form-select" name="direction" id="direction"><option value="higher">ยิ่งมากยิ่งดี</option><option value="lower" <?= ($data['direction']??'')==='lower'?'selected':'' ?>>ยิ่งน้อยยิ่งดี</option></select><small class="text-muted">KPI อัตโนมัติกำหนดวิธีคิดให้ตามประเภท</small></div>
        <div class="col-md-4"><label class="form-label" for="target">เป้าหมาย (มากกว่า 0)</label><input class="form-control" type="number" min="0.0001" max="9999999999.9999" step="0.0001" name="target" id="target" value="<?= Portal::esc($data['target']??'') ?>" required></div>
        <div class="col-md-4"><label class="form-label" for="unit">หน่วย</label><input class="form-control" name="unit" id="unit" maxlength="40" value="<?= Portal::esc($data['unit']??'รายการ') ?>" required></div>
        <div class="col-md-4"><label class="form-label" for="weight">น้ำหนัก (มากกว่า 0 ถึง 100)</label><input class="form-control" type="number" name="weight" id="weight" min="0.0001" max="100" step="0.0001" value="<?= Portal::esc($data['weight']??'1') ?>" required></div>
    </div>
    <div class="alert alert-light border mt-3 small">งานอัตโนมัติอ้างอิงผู้ทำ PM / ผู้กดซ่อมเสร็จและแผนกปัจจุบันของบุคคลนั้น สัดส่วน PM สำเร็จไม่ใช่ตัวชี้วัด PM ตรงเวลา กรุณาตรวจหัวข้อและเดือนให้ถูกต้องก่อนสร้าง</div>
    <button class="btn btn-primary">สร้าง KPI</button> <a class="btn btn-outline-secondary" href="<?= APP_URL ?>/kpi">ยกเลิก</a>
</div></form>
<script>
(() => {
 const source=document.getElementById('source'), direction=document.getElementById('direction'), unit=document.getElementById('unit');
 const sync=()=>{const automatic=source.value!=='manual'; direction.disabled=automatic; unit.readOnly=automatic; if(automatic){ direction.value=source.value==='repair_hours'?'lower':'higher'; unit.value=source.value==='repair_hours'?'ชั่วโมง':source.value==='pm_success_rate'?'%':'รายการ'; }};
 source.addEventListener('change',sync); sync();
 // Disabled controls are not submitted, so restore direction before submit.
 source.form.addEventListener('submit',()=>{direction.disabled=false;});
})();
</script>
