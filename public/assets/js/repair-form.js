(() => {
    const category = document.getElementById('category');
    const machine = document.getElementById('machine-request-fields');
    const generic = document.getElementById('generic-request-fields');
    if (category && machine && generic) {
        const update = () => {
            const selected = category.value === 'machine';
            machine.hidden = machine.disabled = !selected;
            generic.hidden = generic.disabled = selected;
            document.getElementById('repair-form-title').textContent = selected ? 'แจ้งซ่อมและตรวจรับเครื่องจักร' : 'แจ้งซ่อมใหม่';
            document.getElementById('repair-form-description').textContent = selected ? 'MN-F-001-02 · กรอกส่วนแจ้งซ่อมก่อน ส่วนผลซ่อมและตรวจรับจะกรอกตามขั้นตอนงาน' : 'กรอกรายละเอียดปัญหาและแนบรูปภาพประกอบการแจ้งซ่อม';
        };
        category.addEventListener('change', update);
        update();
    }
    const input = document.getElementById('attachment');
    const preview = document.getElementById('repair-photo-preview');
    const empty = document.getElementById('repair-photo-empty');
    let photoUrl;
    if (input && preview && empty) input.addEventListener('change', () => {
        if (photoUrl) URL.revokeObjectURL(photoUrl);
        preview.hidden = true;
        preview.removeAttribute('src');
        empty.hidden = false;
        input.setCustomValidity('');
        const file = input.files[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 10 * 1024 * 1024) {
            input.setCustomValidity('กรุณาเลือกรูป JPG, PNG หรือ WebP ขนาดไม่เกิน 10 MB');
            input.reportValidity();
            return;
        }
        photoUrl = URL.createObjectURL(file);
        preview.src = photoUrl;
        preview.hidden = false;
        empty.hidden = true;
    });
})();
