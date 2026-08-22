document.addEventListener('DOMContentLoaded', () => {
    const panel = document.querySelector('[data-filter-panel]');
    const overlay = document.querySelector('[data-filter-overlay]');
    const openFilters = () => { panel?.classList.add('open'); overlay?.classList.add('open'); document.body.classList.add('filters-open'); };
    const closeFilters = () => { panel?.classList.remove('open'); overlay?.classList.remove('open'); document.body.classList.remove('filters-open'); };
    document.querySelector('[data-filter-open]')?.addEventListener('click', openFilters);
    document.querySelector('[data-filter-close]')?.addEventListener('click', closeFilters);
    overlay?.addEventListener('click', closeFilters);

    document.querySelectorAll('.filter-group-toggle').forEach(button => {
        button.addEventListener('click', () => button.closest('.filter-group')?.classList.toggle('open'));
    });

    document.querySelectorAll('[data-auto-submit]').forEach(select => {
        select.addEventListener('change', () => select.form?.submit());
    });

    const grid = document.querySelector('[data-directory-grid]');
    const viewButtons = [...document.querySelectorAll('[data-view-mode]')];
    const setView = mode => {
        if (!grid) return;
        grid.classList.toggle('list-view', mode === 'list');
        viewButtons.forEach(button => button.classList.toggle('active', button.dataset.viewMode === mode));
        try { localStorage.setItem('aihub-tools-view', mode); } catch (_) {}
    };
    let savedView = 'grid';
    try { savedView = localStorage.getItem('aihub-tools-view') || 'grid'; } catch (_) {}
    setView(savedView);
    viewButtons.forEach(button => button.addEventListener('click', () => setView(button.dataset.viewMode)));

    const tray = document.querySelector('[data-compare-tray]');
    const selectedWrap = document.querySelector('[data-compare-selected]');
    const launch = document.querySelector('[data-compare-launch]');
    const count = document.querySelector('[data-compare-count]');
    const selected = new Map();
    const isAuthenticated = document.querySelector('meta[name="auth-status"]')?.content === '1';
    const loginUrl = document.querySelector('meta[name="login-url"]')?.content || '/auth/login';
    const requireCompareAuth = () => {
        if (isAuthenticated) return true;
        window.location.assign(loginUrl);
        return false;
    };

    const renderCompare = () => {
        if (!selectedWrap || !tray || !launch || !count) return;
        selectedWrap.innerHTML = '';
        selected.forEach((tool, id) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'compare-selected-item';
            item.innerHTML = `<img src="${tool.logo}" alt=""><span>${tool.name}</span><i data-lucide="x"></i>`;
            item.addEventListener('click', () => {
                selected.delete(id);
                document.querySelector(`[data-tool-id="${id}"] [data-compare-tool]`)?.classList.remove('selected');
                renderCompare();
            });
            selectedWrap.appendChild(item);
        });
        tray.classList.toggle('visible', selected.size > 0);
        launch.disabled = selected.size < 2;
        count.textContent = selected.size;
        if (window.lucide) window.lucide.createIcons();
    };

    document.querySelectorAll('[data-compare-tool]').forEach(button => {
        button.addEventListener('click', () => {
            if (!requireCompareAuth()) return;
            const card = button.closest('[data-tool-card]');
            if (!card) return;
            const id = card.dataset.toolId;
            if (selected.has(id)) {
                selected.delete(id);
                button.classList.remove('selected');
            } else {
                if (selected.size >= 4) {
                    tray?.classList.add('limit-pulse');
                    setTimeout(() => tray?.classList.remove('limit-pulse'), 450);
                    return;
                }
                selected.set(id, { name: card.dataset.toolName, logo: card.dataset.toolLogo, rating: card.dataset.toolRating, price: card.dataset.toolPrice, benchmark: card.dataset.toolBenchmark, category: card.dataset.toolCategory, company: card.dataset.toolCompany });
                button.classList.add('selected');
            }
            renderCompare();
        });
    });

    document.querySelector('[data-compare-clear]')?.addEventListener('click', () => {
        selected.clear();
        document.querySelectorAll('[data-compare-tool]').forEach(button => button.classList.remove('selected'));
        renderCompare();
    });

    const compareModal = document.querySelector('[data-quick-compare-modal]');
    const compareTable = document.querySelector('[data-quick-compare-table]');
    const closeCompare = () => {
        compareModal?.classList.remove('open');
        compareModal?.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('quick-compare-open');
    };

    launch?.addEventListener('click', () => {
        if (!requireCompareAuth()) return;
        if (selected.size < 2 || !compareModal || !compareTable) return;
        const tools = [...selected.values()];
        const columns = tools.map(tool => `
            <article class="quick-compare-tool">
                <div class="quick-compare-tool-head"><img src="${tool.logo}" alt=""><div><strong>${tool.name}</strong><span>${tool.company}</span></div></div>
                <dl>
                    <div><dt>Category</dt><dd>${tool.category}</dd></div>
                    <div><dt>User rating</dt><dd class="is-rating">★ ${tool.rating}/5</dd></div>
                    <div><dt>Pricing</dt><dd>${tool.price}</dd></div>
                    <div><dt>Benchmark</dt><dd class="is-score">${tool.benchmark}</dd></div>
                </dl>
            </article>`).join('');
        compareTable.innerHTML = columns;
        compareModal.classList.add('open');
        compareModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('quick-compare-open');
        if (window.lucide) window.lucide.createIcons();
    });

    document.querySelectorAll('[data-quick-compare-close]').forEach(button => button.addEventListener('click', closeCompare));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeCompare(); });

    const filterForm = document.getElementById('tool-filter-form');
    filterForm?.querySelectorAll('input[type="radio"]').forEach(input => {
        input.addEventListener('change', () => {
            if (window.innerWidth > 980) filterForm.submit();
        });
    });
});
