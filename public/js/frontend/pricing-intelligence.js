// AI Orbit pricing UI bundle: 2026-09-19 cache refresh
(() => {
    'use strict';

    const VIEW_KEY = 'aiOrbitPricingView';
    const SELECTION_KEY = 'aiOrbitPricingCompareSelection';
    const MAX_SELECTION = 4;

    const safeParse = (value, fallback) => {
        try {
            const parsed = JSON.parse(value);
            return parsed ?? fallback;
        } catch (_) {
            return fallback;
        }
    };

    const viewButtons = Array.from(document.querySelectorAll('[data-pricing-view-button]'));
    const cardsView = document.querySelector('[data-pricing-cards]');
    const tableView = document.querySelector('[data-pricing-table]');

    const setView = (view) => {
        const normalized = view === 'table' ? 'table' : 'cards';
        if (cardsView) cardsView.hidden = normalized !== 'cards';
        if (tableView) tableView.hidden = normalized !== 'table';

        viewButtons.forEach((button) => {
            const active = button.dataset.pricingViewButton === normalized;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        try { localStorage.setItem(VIEW_KEY, normalized); } catch (_) {}
    };

    if (viewButtons.length) {
        let preferred = 'cards';
        try { preferred = localStorage.getItem(VIEW_KEY) || 'cards'; } catch (_) {}
        setView(preferred);
        viewButtons.forEach((button) => button.addEventListener('click', () => setView(button.dataset.pricingViewButton)));
    }

    const compareForm = document.querySelector('[data-pricing-compare-form]');
    const compareItems = document.querySelector('[data-pricing-compare-items]');
    const compareCount = document.querySelector('[data-pricing-compare-count]');
    const compareNames = document.querySelector('[data-pricing-compare-names]');
    const compareStatus = document.querySelector('[data-pricing-compare-status]');
    const compareSubmit = document.querySelector('[data-pricing-compare-submit]');
    const compareClear = document.querySelector('[data-pricing-compare-clear]');
    const selectionInputs = Array.from(document.querySelectorAll('[data-pricing-select]'));

    let selection = [];
    try {
        const stored = safeParse(sessionStorage.getItem(SELECTION_KEY), []);
        selection = Array.isArray(stored)
            ? stored.filter((item) => item && Number.isFinite(Number(item.id)) && item.name).slice(0, MAX_SELECTION)
            : [];
    } catch (_) {
        selection = [];
    }

    const persistSelection = () => {
        try { sessionStorage.setItem(SELECTION_KEY, JSON.stringify(selection)); } catch (_) {}
    };

    const syncSelectionUI = (message = '') => {
        const selectedIds = new Set(selection.map((item) => String(item.id)));

        selectionInputs.forEach((input) => {
            const selected = selectedIds.has(String(input.dataset.toolId));
            input.checked = selected;
            input.closest('[data-tool-card]')?.classList.toggle('is-selected', selected);
            input.closest('[data-tool-row]')?.classList.toggle('is-selected', selected);
        });

        if (compareForm) compareForm.hidden = selection.length === 0;
        if (compareCount) compareCount.textContent = `${selection.length} selected`;
        if (compareSubmit) compareSubmit.disabled = selection.length < 2;

        if (compareItems) {
            compareItems.innerHTML = '';
            selection.forEach((item) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[]';
                input.value = String(item.id);
                compareItems.appendChild(input);
            });
        }

        if (compareNames) {
            compareNames.innerHTML = '';
            selection.forEach((item) => {
                const chip = document.createElement('span');
                chip.textContent = item.name;
                compareNames.appendChild(chip);
            });
        }

        if (compareStatus) {
            if (message) {
                compareStatus.textContent = message;
            } else if (selection.length < 2) {
                compareStatus.textContent = 'Select at least two tools to compare.';
            } else if (selection.length === MAX_SELECTION) {
                compareStatus.textContent = 'Maximum four tools selected.';
            } else {
                compareStatus.textContent = 'Ready for a pricing-focused side-by-side comparison.';
            }
        }

        persistSelection();
    };

    selectionInputs.forEach((input) => {
        input.addEventListener('change', () => {
            const id = String(input.dataset.toolId || '');
            const name = String(input.dataset.toolName || '').trim();
            const existingIndex = selection.findIndex((item) => String(item.id) === id);

            if (input.checked && existingIndex === -1) {
                if (selection.length >= MAX_SELECTION) {
                    input.checked = false;
                    syncSelectionUI('You can compare up to four tools at once.');
                    return;
                }
                selection.push({ id: Number(id), name });
            } else if (!input.checked && existingIndex !== -1) {
                selection.splice(existingIndex, 1);
            }

            syncSelectionUI();
        });
    });

    compareClear?.addEventListener('click', () => {
        selection = [];
        syncSelectionUI();
    });

    compareForm?.addEventListener('submit', (event) => {
        if (selection.length < 2 || selection.length > MAX_SELECTION) {
            event.preventDefault();
            syncSelectionUI('Select between two and four tools before comparing.');
        }
    });

    syncSelectionUI();

    const changeButtons = Array.from(document.querySelectorAll('[data-change-filter]'));
    const changeCards = Array.from(document.querySelectorAll('[data-change-card]'));

    changeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const filter = button.dataset.changeFilter || 'all';
            changeButtons.forEach((candidate) => {
                const active = candidate === button;
                candidate.classList.toggle('active', active);
                candidate.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            changeCards.forEach((card) => {
                card.hidden = filter !== 'all' && card.dataset.changeType !== filter;
            });
        });
    });
})();
