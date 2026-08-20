document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) window.lucide.createIcons();

    const menuButton = document.querySelector('[data-menu-toggle]');
    const mobileNav = document.querySelector('[data-mobile-nav]');
    const mobileNavBackdrop = document.querySelector('[data-mobile-nav-backdrop]');

    const setMobileMenu = (open) => {
        if (!menuButton || !mobileNav) return;

        mobileNav.classList.toggle('open', open);
        mobileNav.style.removeProperty('display');
        document.body.classList.toggle('mobile-menu-open', open);
        menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        menuButton.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        mobileNav.setAttribute('aria-hidden', open ? 'false' : 'true');
    };

    menuButton?.setAttribute('aria-expanded', 'false');
    menuButton?.addEventListener('click', () => {
        setMobileMenu(!mobileNav?.classList.contains('open'));
    });

    mobileNavBackdrop?.addEventListener('click', () => setMobileMenu(false));

    mobileNav?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setMobileMenu(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMobileMenu(false);
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 1100) setMobileMenu(false);
    });

    const search = document.getElementById('home-global-search');
    document.querySelector('[data-focus-search]')?.addEventListener('click', () => search?.focus());

    const cards = [...document.querySelectorAll('[data-tool-grid] [data-category]')];
    const empty = document.querySelector('[data-empty-state]');
    let category = 'all';

    const applyFilter = () => {
        let visible = 0;
        cards.forEach(card => {
            const categoryMatch = category === 'all' || card.dataset.category === category;
            const show = categoryMatch;
            card.hidden = !show;
            if (show) visible++;
        });
        if (empty) empty.hidden = visible !== 0;
    };

    document.querySelectorAll('[data-tool-tabs] button').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-tool-tabs] button').forEach(b => b.classList.remove('active'));
            button.classList.add('active');
            category = button.dataset.filter || 'all';
            applyFilter();
        });
    });

    document.querySelectorAll('[data-category-filter]').forEach(link => {
        link.addEventListener('click', () => {
            category = link.dataset.categoryFilter || 'all';
            const matchingTab = document.querySelector(`[data-tool-tabs] button[data-filter="${category}"]`);
            if (matchingTab) {
                document.querySelectorAll('[data-tool-tabs] button').forEach(b => b.classList.remove('active'));
                matchingTab.classList.add('active');
            }
            setTimeout(applyFilter, 0);
        });
    });

});
