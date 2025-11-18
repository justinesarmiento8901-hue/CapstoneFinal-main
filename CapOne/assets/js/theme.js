document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    const closeSidebar = () => {
        if (!sidebar) return;
        sidebar.classList.remove('active');
        document.body.classList.remove('sidebar-open');
    };

    const openSidebar = () => {
        if (!sidebar) return;
        sidebar.classList.add('active');
        document.body.classList.add('sidebar-open');
    };

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            if (sidebar.classList.contains('active')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    document.addEventListener('click', (event) => {
        if (!sidebar || !toggleBtn) return;
        const clickedInsideSidebar = sidebar.contains(event.target);
        const clickedToggle = toggleBtn.contains(event.target);
        if (!clickedInsideSidebar && !clickedToggle && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 992) {
            sidebar?.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    });
});
