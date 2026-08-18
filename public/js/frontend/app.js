document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) window.lucide.createIcons();

    const menuButton = document.querySelector('[data-menu-toggle]');
    const mobileNav = document.querySelector('[data-mobile-nav]');
    menuButton?.addEventListener('click', () => mobileNav?.classList.toggle('open'));

    const search = document.getElementById('home-global-search');
    document.querySelector('[data-focus-search]')?.addEventListener('click', () => search?.focus());

    const cards = [...document.querySelectorAll('[data-tool-grid] [data-category]')];
    const empty = document.querySelector('[data-empty-state]');
    let category = 'all';

    const applyFilter = () => {
        const query = (search?.value || '').trim().toLowerCase();
        let visible = 0;
        cards.forEach(card => {
            const categoryMatch = category === 'all' || card.dataset.category === category;
            const searchMatch = !query || (card.dataset.search || '').includes(query);
            const show = categoryMatch && searchMatch;
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

    search?.addEventListener('input', applyFilter);
});
