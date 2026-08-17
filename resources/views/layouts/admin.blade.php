<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="color-scheme" content="dark">
<title>@yield('title', 'Dashboard') · AI Intelligence Admin</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
@stack('styles')
</head>
<body>
<div class="app-shell">
    @include('partials.sidebar')
    <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Close navigation"></button>

    <div class="main-col">
        @include('partials.header')
        <main class="content" id="mainContent">
            @yield('content')
        </main>
    </div>
</div>

@include('partials.preview-modal')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');
    const desktopMq = window.matchMedia('(min-width: 901px)');

    const renderIcons = () => {
        if (window.lucide) window.lucide.createIcons();
    };

    const setMobileOpen = (open) => {
        if (!sidebar) return;
        sidebar.classList.toggle('is-mobile-open', open);
        document.body.classList.toggle('sidebar-mobile-open', open);
        sidebarToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    const syncSidebar = () => {
        if (!sidebar) return;
        if (desktopMq.matches) {
            setMobileOpen(false);
            sidebar.classList.toggle('is-collapsed', localStorage.getItem('aihub.sidebar.collapsed') === '1');
        } else {
            sidebar.classList.remove('is-collapsed');
        }
    };

    syncSidebar();
    desktopMq.addEventListener?.('change', syncSidebar);

    sidebarToggle?.addEventListener('click', function () {
        if (!sidebar) return;
        if (desktopMq.matches) {
            const collapsed = !sidebar.classList.contains('is-collapsed');
            sidebar.classList.toggle('is-collapsed', collapsed);
            localStorage.setItem('aihub.sidebar.collapsed', collapsed ? '1' : '0');
        } else {
            setMobileOpen(!sidebar.classList.contains('is-mobile-open'));
        }
    });

    backdrop?.addEventListener('click', () => setMobileOpen(false));

    document.querySelectorAll('#sidebar a').forEach(link => {
        link.addEventListener('click', () => {
            if (!desktopMq.matches) setMobileOpen(false);
        });
    });

    const closeDropdowns = (except = null) => {
        document.querySelectorAll('.dropdown-menu-custom.is-open').forEach(menu => {
            if (menu !== except) menu.classList.remove('is-open');
        });
    };

    document.querySelectorAll('[data-dropdown-toggle]').forEach(btn => {
        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            const selector = btn.getAttribute('data-dropdown-toggle');
            const menu = selector ? document.querySelector(selector) : null;
            if (!menu) return;
            const willOpen = !menu.classList.contains('is-open');
            closeDropdowns(menu);
            menu.classList.toggle('is-open', willOpen);
            btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.dropdown-menu-custom')) closeDropdowns();
    });

    const searchModal = document.getElementById('globalSearchModal');
    const searchInput = document.getElementById('adminNavSearch');
    const navItems = [...document.querySelectorAll('[data-nav-search-item]')];

    const filterNavigation = () => {
        const term = (searchInput?.value || '').trim().toLowerCase();
        navItems.forEach(item => {
            item.hidden = !!term && !item.textContent.toLowerCase().includes(term);
        });
        const empty = document.getElementById('adminNavSearchEmpty');
        if (empty) empty.hidden = navItems.some(item => !item.hidden);
    };

    searchInput?.addEventListener('input', filterNavigation);

    searchModal?.addEventListener('shown.bs.modal', () => {
        if (searchInput) {
            searchInput.value = '';
            filterNavigation();
            searchInput.focus();
        }
    });

    document.addEventListener('keydown', function (event) {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            if (searchModal && window.bootstrap) bootstrap.Modal.getOrCreateInstance(searchModal).show();
        }
        if (event.key === 'Escape') {
            closeDropdowns();
            if (!desktopMq.matches) setMobileOpen(false);
        }
    });

    renderIcons();
});
</script>
@stack('scripts')
</body>
</html>
