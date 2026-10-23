document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
        const root = document.documentElement;
        const applySidebar = collapsed => {
            root.classList.toggle('sidebar-collapsed', collapsed);
            sidebarToggle.setAttribute('aria-expanded', String(!collapsed));
            const label = collapsed ? 'ขยายเมนูด้านข้าง' : 'ย่อเมนูด้านข้าง';
            sidebarToggle.setAttribute('aria-label', label);
            sidebarToggle.title = label;
        };
        applySidebar(root.classList.contains('sidebar-collapsed'));
        sidebarToggle.addEventListener('click', () => {
            const collapsed = !root.classList.contains('sidebar-collapsed');
            applySidebar(collapsed);
            try { localStorage.setItem('svp.sidebar.collapsed', collapsed ? '1' : '0'); }
            catch (_) { /* Still allow toggling in private/restricted browser contexts. */ }
        });
        // Expand the rail before opening nested document categories.
        document.querySelectorAll('.dcc-nav-group > summary').forEach(summary => {
            summary.addEventListener('click', event => {
                if (!window.matchMedia('(min-width: 992px)').matches || !root.classList.contains('sidebar-collapsed')) return;
                event.preventDefault();
                sidebarToggle.click();
                summary.parentElement.open = true;
            });
        });
    }
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
