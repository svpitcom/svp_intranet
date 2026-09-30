document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.password-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const field = document.getElementById(button.getAttribute('aria-controls'));
            if (!field) return;
            const visible = field.type === 'password';
            field.type = visible ? 'text' : 'password';
            button.textContent = visible ? 'ซ่อน' : 'แสดง';
            button.setAttribute('aria-pressed', String(visible));
        });
    });
    // Give existing form labels an explicit association without changing request field names.
    document.querySelectorAll('label:not([for])').forEach((label, index) => {
        const field = label.parentElement.querySelector('input:not([type="hidden"]), select, textarea');
        if (!field || label.contains(field)) return;
        if (!field.id) field.id = 'form-field-' + index;
        label.htmlFor = field.id;
    });
    document.querySelectorAll('.table-responsive').forEach(table => {
        table.tabIndex = 0;
        table.setAttribute('role', 'region');
        table.setAttribute('aria-label', 'ตารางข้อมูล เลื่อนแนวนอนเพื่อดูเพิ่มเติม');
    });
});
