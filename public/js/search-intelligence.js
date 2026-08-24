(() => {
    const suggestUrl = document.querySelector('meta[name="search-suggest-url"]')?.content;
    const clickUrl = document.querySelector('meta[name="search-click-url"]')?.content;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    if (!suggestUrl) return;

    const iconByType = {
        tools: 'bot', models: 'cpu', news: 'radio', companies: 'building-2',
        articles: 'newspaper', comparisons: 'scale', benchmarks: 'gauge', tests: 'flask-conical', category: 'layers-3',
        feature: 'sparkles', 'use-case': 'target'
    };

    const refreshIcons = () => {
        if (window.lucide) window.lucide.createIcons();
    };

    const trackClick = (query, targetType, targetId) => {
        if (!clickUrl || !query || !targetType || !targetId) return;
        fetch(clickUrl, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ query, target_type: targetType, target_id: Number(targetId) })
        }).catch(() => {});
    };

    const makeIcon = (type, image, label) => {
        const wrap = document.createElement('span');
        if (image) {
            wrap.className = 'search-live-thumb';
            const img = document.createElement('img');
            img.src = image;
            img.alt = label ? `${label} logo` : '';
            img.loading = 'lazy';
            img.addEventListener('error', () => {
                wrap.className = 'search-live-kind';
                wrap.textContent = '';
                const i = document.createElement('i');
                i.dataset.lucide = iconByType[type] || 'search';
                wrap.appendChild(i);
                refreshIcons();
            }, { once: true });
            wrap.appendChild(img);
            return wrap;
        }
        wrap.className = 'search-live-kind';
        const i = document.createElement('i');
        i.dataset.lucide = iconByType[type] || 'search';
        wrap.appendChild(i);
        return wrap;
    };

    const initAutocomplete = (input) => {
        const shell = input.closest('[data-search-shell]');
        const panel = shell?.querySelector('[data-search-suggestions]');
        if (!shell || !panel) return;

        let timer = null;
        let controller = null;
        let activeIndex = -1;

        const homeHero = shell.closest('.home-hero-reference');
        const hide = () => {
            panel.hidden = true;
            panel.replaceChildren();
            activeIndex = -1;
            homeHero?.classList.remove('search-suggestions-open');
        };

        const links = () => [...panel.querySelectorAll('a.search-live-item')];
        const setActive = (index) => {
            const items = links();
            if (!items.length) return;
            activeIndex = Math.max(0, Math.min(index, items.length - 1));
            items.forEach((item, i) => item.classList.toggle('is-active', i === activeIndex));
            items[activeIndex]?.scrollIntoView({ block: 'nearest' });
        };

        const render = (payload) => {
            panel.replaceChildren();
            activeIndex = -1;

            const label = document.createElement('div');
            label.className = 'search-live-label';
            const labelText = document.createElement('span');
            labelText.textContent = 'Best matches';
            const labelHint = document.createElement('span');
            labelHint.textContent = `${payload.suggestions?.length || 0} suggestions`;
            label.append(labelText, labelHint);
            panel.appendChild(label);

            (payload.suggestions || []).forEach((item) => {
                const a = document.createElement('a');
                a.className = 'search-live-item';
                a.href = item.url;
                if (item.target_type && item.target_id) {
                    a.dataset.searchResult = '1';
                    a.dataset.searchQuery = payload.query || input.value.trim();
                    a.dataset.searchTargetType = item.target_type;
                    a.dataset.searchTargetId = item.target_id;
                }

                a.appendChild(makeIcon(item.type, item.image, item.label));

                const copy = document.createElement('span');
                copy.className = 'search-live-copy';
                const strong = document.createElement('strong');
                strong.textContent = item.label || 'Result';
                const meta = document.createElement('span');
                meta.textContent = item.meta || item.type || '';
                copy.append(strong, meta);
                a.appendChild(copy);

                const arrow = document.createElement('span');
                arrow.className = 'search-live-arrow';
                const arrowIcon = document.createElement('i');
                arrowIcon.dataset.lucide = 'arrow-up-right';
                arrow.appendChild(arrowIcon);
                a.appendChild(arrow);
                panel.appendChild(a);
            });

            if (!payload.suggestions?.length) {
                const empty = document.createElement('div');
                empty.className = 'search-live-empty';
                empty.append(document.createTextNode('No instant match. '));
                if (payload.correction) {
                    const correction = document.createElement('a');
                    correction.href = `${shell.action || '/search'}?q=${encodeURIComponent(payload.correction)}`;
                    correction.textContent = `Try “${payload.correction}”`;
                    empty.appendChild(correction);
                } else {
                    empty.append(document.createTextNode('Press Enter for full search.'));
                }
                panel.appendChild(empty);
            }

            const footer = document.createElement('div');
            footer.className = 'search-live-footer';
            const hint = document.createElement('span');
            hint.textContent = 'Relevance ranked across AI Orbit';
            const all = document.createElement('a');
            all.href = `${shell.action || '/search'}?q=${encodeURIComponent(payload.query || input.value.trim())}`;
            all.textContent = 'See all results →';
            footer.append(hint, all);
            panel.appendChild(footer);

            panel.hidden = false;
            homeHero?.classList.add('search-suggestions-open');
            refreshIcons();
        };

        const load = async () => {
            const q = input.value.trim();
            if (q.length < 2) {
                hide();
                return;
            }
            controller?.abort();
            controller = new AbortController();
            try {
                const url = new URL(suggestUrl, window.location.origin);
                url.searchParams.set('q', q);
                url.searchParams.set('limit', '10');
                const response = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal
                });
                if (!response.ok) throw new Error('Search suggestions failed');
                render(await response.json());
            } catch (error) {
                if (error.name !== 'AbortError') hide();
            }
        };

        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(load, 220);
        });
        input.addEventListener('focus', () => {
            if (input.value.trim().length >= 2 && panel.childElementCount) panel.hidden = false;
        });
        input.addEventListener('keydown', (event) => {
            if (panel.hidden) return;
            const items = links();
            if (event.key === 'ArrowDown' && items.length) {
                event.preventDefault(); setActive(activeIndex + 1);
            } else if (event.key === 'ArrowUp' && items.length) {
                event.preventDefault(); setActive(activeIndex <= 0 ? items.length - 1 : activeIndex - 1);
            } else if (event.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
                event.preventDefault(); items[activeIndex].click();
            } else if (event.key === 'Escape') {
                hide();
            }
        });
        document.addEventListener('click', (event) => {
            if (!shell.contains(event.target)) hide();
        });
    };

    document.querySelectorAll('[data-search-autocomplete]').forEach(initAutocomplete);

    document.addEventListener('click', (event) => {
        const result = event.target.closest('[data-search-result]');
        if (!result) return;
        trackClick(result.dataset.searchQuery, result.dataset.searchTargetType, result.dataset.searchTargetId);
    });

    const modal = document.querySelector('[data-site-search-modal]');
    const overlayInput = modal?.querySelector('[data-search-overlay-input]');
    const openButtons = [...document.querySelectorAll('[data-global-search-open]')];
    const closeButtons = [...(modal?.querySelectorAll('[data-global-search-close]') || [])];

    const openModal = () => {
        if (!modal) return;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('site-search-open');
        requestAnimationFrame(() => overlayInput?.focus());
        refreshIcons();
    };
    const closeModal = () => {
        if (!modal) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('site-search-open');
    };

    openButtons.forEach((button) => button.addEventListener('click', (event) => {
        if (!modal) return;
        event.preventDefault();
        openModal();
    }));
    closeButtons.forEach((button) => button.addEventListener('click', closeModal));

    document.addEventListener('keydown', (event) => {
        const target = event.target;
        const typing = target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement || target?.isContentEditable;
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            modal?.hidden ? openModal() : closeModal();
        } else if (event.key === '/' && !typing && modal?.hidden) {
            event.preventDefault();
            openModal();
        } else if (event.key === 'Escape' && modal && !modal.hidden) {
            closeModal();
        }
    });
})();
