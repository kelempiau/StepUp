/**
 * Admin Panel Global Scripts
 * Handles Sidebar Toggle & Active Link Highlighting
 */

// Toggle Sidebar (Overlay style for Mobile)
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    if (sidebar.classList.contains('hidden')) {
        sidebar.classList.remove('hidden');
        sidebar.classList.add('fixed', 'inset-0', 'flex');
    } else {
        sidebar.classList.add('hidden');
        sidebar.classList.remove('fixed', 'inset-0', 'flex');
    }
}

// Active Link Highlighting
document.addEventListener("DOMContentLoaded", function () {
    const currentPath = window.location.pathname;

    // Navigation Links Mapping
    const navLinks = [
        { href: 'dashboard.php', el: document.querySelector('nav a[href="dashboard.php"]') },
        { href: 'students.php', el: document.querySelector('nav a[href="students.php"]') },
        { href: 'subjects.php', el: document.querySelector('nav a[href="subjects.php"]'), related: ['topics.php', 'modules.php', 'edit_module.php'] },
        { href: 'activities.php', el: document.querySelector('nav a[href="activities.php"]') },
        { href: 'quotes.php', el: document.querySelector('nav a[href="quotes.php"]') }
    ];

    // Active State Classes (Blue filled style)
    const activeClasses = "bg-blue-600 text-white font-bold shadow-xl shadow-blue-500/20".split(" ");

    // Inactive State Classes (Gray/Blue text style)
    const inactiveClasses = "text-slate-500 dark:text-blue-400/60 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400".split(" ");

    navLinks.forEach(item => {
        if (!item.el) return;

        let isActive = currentPath.includes(item.href);

        if (item.related) {
            item.related.forEach(rel => {
                if (currentPath.includes(rel)) isActive = true;
            });
        }

        if (isActive) {
            item.el.classList.remove(...inactiveClasses);
            item.el.classList.add(...activeClasses);
        }
    });
});
