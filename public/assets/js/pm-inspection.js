(() => {
    const form = document.getElementById('pm-inspection-form');
    if (!form) return;
    const start = form.elements.start_time, end = form.elements.end_time;
    const duration = document.getElementById('pm-duration');
    function updateDuration() {
        if (!start.value || !end.value) { duration.textContent = 'รวมเวลา: —'; end.setCustomValidity(''); return; }
        const minutes = value => Number(value.slice(0,2)) * 60 + Number(value.slice(3,5));
        const total = minutes(end.value) - minutes(start.value);
        end.setCustomValidity(total < 0 ? 'เวลาสิ้นสุดต้องไม่น้อยกว่าเวลาเริ่ม' : '');
        duration.textContent = total < 0 ? 'กรุณาตรวจสอบเวลาเริ่ม / สิ้นสุด' : `รวมเวลา: ${total} นาที`;
    }
    start.addEventListener('input', updateDuration); end.addEventListener('input', updateDuration);
    form.addEventListener('input', () => { document.getElementById('pm-save-hint').textContent = 'มีข้อมูลที่ยังไม่ได้บันทึก'; });
    form.addEventListener('submit', event => {
        if (form.dataset.submitting) { event.preventDefault(); return; }
        form.dataset.submitting = '1';
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true; button.textContent = 'กำลังบันทึก…';
    });
    window.addEventListener('pageshow', () => { delete form.dataset.submitting; const button=form.querySelector('button[type="submit"]'); button.disabled=false; button.textContent='บันทึกผลตรวจ PM'; });
    updateDuration();
})();
