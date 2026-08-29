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
                let benchmarks = [];
                try { benchmarks = JSON.parse(card.dataset.toolBenchmarks || '[]'); } catch (_) {}
                selected.set(id, {
                    id,
                    name: card.dataset.toolName,
                    logo: card.dataset.toolLogo,
                    rating: card.dataset.toolRating || '',
                    price: card.dataset.toolPrice || 'Pricing varies',
                    category: card.dataset.toolCategory || 'AI Tool',
                    company: card.dataset.toolCompany || 'Independent',
                    platforms: (card.dataset.toolPlatforms || '').split('|').filter(Boolean),
                    capabilities: (card.dataset.toolCapabilities || '').split('|').filter(Boolean),
                    useCases: (card.dataset.toolUseCases || '').split('|').filter(Boolean),
                    benchmarks
                });
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
    const compareDialog = document.querySelector('[data-quick-compare-dialog]');
    const compareTable = document.querySelector('[data-quick-compare-table]');
    const compareTitle = document.querySelector('[data-quick-compare-title]');
    const compareSubtitle = document.querySelector('[data-quick-compare-subtitle]');
    const compareEvidence = document.querySelector('[data-quick-compare-evidence]');
    const fullCompareLink = document.querySelector('[data-quick-compare-full]');

    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    })[char]);

    const listChips = (items, emptyLabel) => {
        const clean = [...new Set((items || []).filter(Boolean))].slice(0, 4);
        if (!clean.length) return `<span class="quick-empty-chip">${escapeHtml(emptyLabel)}</span>`;
        return clean.map(item => `<span>${escapeHtml(item)}</span>`).join('');
    };

    const sharedBenchmarkRows = tools => {
        if (tools.length < 2) return [];
        const maps = tools.map(tool => new Map((tool.benchmarks || []).map(row => [String(row.id), row])));
        const sharedIds = [...maps[0].keys()].filter(id => maps.every(map => map.has(id)));

        return sharedIds.map(id => {
            const entries = maps.map((map, index) => ({ tool: tools[index], benchmark: map.get(id) }));
            const higherIsBetter = entries[0].benchmark.higher_is_better !== false;
            const scores = entries.map(entry => Number(entry.benchmark.score));
            const target = higherIsBetter ? Math.max(...scores) : Math.min(...scores);
            const winners = entries.filter(entry => Number(entry.benchmark.score) === target).map(entry => entry.tool.id);
            return {
                id,
                name: entries[0].benchmark.name || 'Verified benchmark',
                higherIsBetter,
                entries,
                winners
            };
        });
    };

    const evidenceSummary = (tools, shared) => {
        if (!shared.length) {
            return {
                label: 'Limited evidence',
                text: 'No shared verified benchmark is available for all selected tools. Compare product fit, capabilities, use cases and pricing instead.'
            };
        }

        const singleWinners = shared
            .filter(row => row.winners.length === 1)
            .map(row => row.winners[0]);

        if (singleWinners.length && singleWinners.every(id => id === singleWinners[0])) {
            const leader = tools.find(tool => tool.id === singleWinners[0]);
            return {
                label: 'Evidence lead',
                text: `${leader?.name || 'One tool'} leads the shared verified benchmark evidence shown below.`
            };
        }

        return {
            label: 'Mixed evidence',
            text: 'The selected tools split or tie the shared benchmark evidence, so there is no clear benchmark winner.'
        };
    };

    const renderEvidence = (tools, shared) => {
        if (!compareEvidence) return;
        const summary = evidenceSummary(tools, shared);
        const rows = shared.slice(0, 3).map(row => {
            const scores = row.entries.map(entry => {
                const isWinner = row.winners.includes(entry.tool.id);
                return `<span class="quick-evidence-score ${isWinner ? 'winner' : ''}"><b>${escapeHtml(entry.tool.name)}</b><strong>${escapeHtml(entry.benchmark.score)}</strong>${isWinner ? '<em>Best</em>' : ''}</span>`;
            }).join('');
            return `<div class="quick-evidence-row"><div><strong>${escapeHtml(row.name)}</strong><small>${row.higherIsBetter ? 'Higher is better' : 'Lower is better'}</small></div><div class="quick-evidence-scores">${scores}</div></div>`;
        }).join('');

        compareEvidence.innerHTML = `
            <div class="quick-verdict"><span><i data-lucide="badge-check"></i>${escapeHtml(summary.label)}</span><p>${escapeHtml(summary.text)}</p></div>
            ${rows ? `<div class="quick-evidence-list"><div class="quick-evidence-title"><i data-lucide="gauge"></i> Shared verified benchmarks</div>${rows}</div>` : ''}
        `;
    };

    const closeCompare = () => {
        compareModal?.classList.remove('open');
        compareModal?.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('quick-compare-open');
    };

    launch?.addEventListener('click', () => {
        if (!requireCompareAuth()) return;
        if (selected.size < 2 || !compareModal || !compareTable) return;

        const tools = [...selected.values()];
        const shared = sharedBenchmarkRows(tools);
        const sharedByTool = new Map();
        shared.forEach(row => row.entries.forEach(entry => {
            if (!sharedByTool.has(entry.tool.id)) sharedByTool.set(entry.tool.id, []);
            sharedByTool.get(entry.tool.id).push({ row, entry });
        }));

        compareDialog?.setAttribute('data-count', String(tools.length));
        if (compareTitle) compareTitle.textContent = tools.length === 2
            ? `${tools[0].name} vs ${tools[1].name}`
            : `${tools.length} AI tools selected`;
        if (compareSubtitle) compareSubtitle.textContent = `Fast verified snapshot · ${tools.length} selected tools · directory data`;

        compareTable.innerHTML = tools.map(tool => {
            const toolShared = sharedByTool.get(tool.id) || [];
            const benchmarkText = toolShared.length
                ? `${toolShared.length} shared verified benchmark${toolShared.length > 1 ? 's' : ''}`
                : 'No shared benchmark';
            const platforms = tool.platforms.length ? tool.platforms.join(', ') : 'Not listed';
            const rating = tool.rating ? `★ ${escapeHtml(tool.rating)}/5` : 'Not rated yet';

            return `
                <article class="quick-compare-tool">
                    <div class="quick-compare-tool-head">
                        <img src="${escapeHtml(tool.logo)}" alt="${escapeHtml(tool.name)} logo">
                        <div><strong>${escapeHtml(tool.name)}</strong><span>${escapeHtml(tool.company)}</span><small>${escapeHtml(tool.category)}</small></div>
                    </div>
                    <dl>
                        <div><dt>Pricing</dt><dd>${escapeHtml(tool.price)}</dd></div>
                        <div><dt>Platforms</dt><dd>${escapeHtml(platforms)}</dd></div>
                        <div><dt>Community</dt><dd class="${tool.rating ? 'is-rating' : 'is-muted'}">${rating}</dd></div>
                        <div><dt>Benchmark</dt><dd class="${toolShared.length ? 'is-score' : 'is-muted'}">${escapeHtml(benchmarkText)}</dd></div>
                    </dl>
                    <div class="quick-taxonomy-block"><small>Top capabilities</small><div class="quick-chip-row">${listChips(tool.capabilities, 'Capabilities not listed')}</div></div>
                    <div class="quick-taxonomy-block"><small>Best use cases</small><div class="quick-chip-row use-cases">${listChips(tool.useCases, 'Use cases not listed')}</div></div>
                </article>`;
        }).join('');

        renderEvidence(tools, shared);

        if (fullCompareLink) {
            const previewUrl = fullCompareLink.dataset.previewUrl || '/compare/preview';
            const params = new URLSearchParams({ type: 'tool' });
            tools.forEach(tool => params.append('items[]', tool.id));
            fullCompareLink.href = `${previewUrl}?${params.toString()}`;
        }

        compareModal.classList.add('open');
        compareModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('quick-compare-open');
        if (window.lucide) window.lucide.createIcons();
    });

    document.querySelectorAll('[data-quick-compare-close]').forEach(button => button.addEventListener('click', closeCompare));
    document.querySelector('[data-quick-compare-change]')?.addEventListener('click', closeCompare);
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeCompare(); });

    const filterForm = document.getElementById('tool-filter-form');
    filterForm?.querySelectorAll('input[type="radio"]').forEach(input => {
        input.addEventListener('change', () => {
            if (window.innerWidth > 980) filterForm.submit();
        });
    });
});
