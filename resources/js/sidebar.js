export function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggle = document.getElementById('sidebar-toggle');
    const collapseBtn = document.getElementById('sidebar-collapse');

    if (!sidebar) return;

    const collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
    if (collapsed) sidebar.classList.add('lg:w-20');

    toggle?.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
        overlay?.classList.toggle('hidden');
    });

    overlay?.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    });

    collapseBtn?.addEventListener('click', () => {
        sidebar.classList.toggle('lg:w-20');
        sidebar.classList.toggle('lg:w-64');
        const isCollapsed = sidebar.classList.contains('lg:w-20');
        localStorage.setItem('sidebar-collapsed', isCollapsed);
        document.querySelectorAll('[data-sidebar-label]').forEach((el) => {
            el.classList.toggle('lg:hidden', isCollapsed);
        });
    });

    document.querySelectorAll('[data-submenu-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.submenuToggle);
            target?.classList.toggle('hidden');
            btn.querySelector('[data-chevron]')?.classList.toggle('rotate-180');
        });
    });
}
