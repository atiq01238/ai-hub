document.addEventListener('DOMContentLoaded', () => {
    const toggleUrl = document.querySelector('meta[name="saved-toggle-url"]')?.content;
    const intentUrl = document.querySelector('meta[name="saved-intent-url"]')?.content || '/saved/intent';
    const statusUrl = document.querySelector('meta[name="saved-status-url"]')?.content;
    const loginUrl = document.querySelector('meta[name="login-url"]')?.content || '/auth/login';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const buttons = [...document.querySelectorAll('[data-save-item]')];

    if (!buttons.length || !toggleUrl || !statusUrl) return;

    const showToast = message => {
        let toast = document.querySelector('.save-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'save-toast';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.classList.add('show');
        clearTimeout(window.__aiHubSaveToast);
        window.__aiHubSaveToast = window.setTimeout(() => toast.classList.remove('show'), 2200);
    };

    const applyState = (button, saved) => {
        button.classList.toggle('saved', saved);
        button.setAttribute('aria-pressed', saved ? 'true' : 'false');
        const label = button.querySelector('[data-save-label]');
        if (label) label.textContent = saved ? 'Saved' : (label.dataset.defaultLabel || 'Save');
    };

    const rememberGuestIntentAndLogin = async button => {
        if (!intentUrl) {
            window.location.assign(loginUrl);
            return;
        }

        try {
            const response = await fetch(intentUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    type: button.dataset.saveType,
                    id: Number(button.dataset.saveId),
                    return_to: window.location.href
                })
            });

            if (!response.ok) throw new Error('intent-failed');

            const data = await response.json();
            window.location.assign(data.login_url || loginUrl);
        } catch (_) {
            window.location.assign(loginUrl);
        }
    };

    const groups = new Map();
    buttons.forEach(button => {
        const type = button.dataset.saveType;
        const id = button.dataset.saveId;
        if (!type || !id) return;
        if (!groups.has(type)) groups.set(type, new Set());
        groups.get(type).add(id);
    });

    groups.forEach(async (ids, type) => {
        try {
            const url = new URL(statusUrl, window.location.origin);
            url.searchParams.set('type', type);
            url.searchParams.set('ids', [...ids].join(','));
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (!response.ok) return;

            const data = await response.json();
            if (!data.authenticated) return;

            const savedIds = new Set((data.saved_ids || []).map(String));
            buttons
                .filter(button => button.dataset.saveType === type)
                .forEach(button => applyState(button, savedIds.has(String(button.dataset.saveId))));
        } catch (_) {
            // Status hydration is non-critical; buttons still work on click.
        }
    });

    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-save-item]');
        if (!button) return;

        event.preventDefault();
        if (button.dataset.saving === 'true') return;

        button.dataset.saving = 'true';
        button.disabled = true;

        try {
            const response = await fetch(toggleUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    type: button.dataset.saveType,
                    id: Number(button.dataset.saveId)
                })
            });

            if (response.status === 401) {
                await rememberGuestIntentAndLogin(button);
                return;
            }

            if (response.status === 419) {
                showToast('Your session expired. Refresh the page and try again.');
                return;
            }

            if (response.status === 403) {
                showToast('Your account cannot perform this action.');
                return;
            }

            if (!response.ok) throw new Error('save-failed');

            const data = await response.json();
            document
                .querySelectorAll(`[data-save-item][data-save-type="${button.dataset.saveType}"][data-save-id="${button.dataset.saveId}"]`)
                .forEach(match => applyState(match, Boolean(data.saved)));

            showToast(data.message || (data.saved ? 'Saved.' : 'Removed.'));

            if (!data.saved && button.dataset.removeCard === 'true') {
                const card = button.closest('[data-saved-card]');
                if (card) {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(.98)';
                    window.setTimeout(() => {
                        card.remove();
                        if (!document.querySelector('[data-saved-card]')) window.location.reload();
                    }, 180);
                }
            }
        } catch (_) {
            showToast('Could not update your saved library. Please try again.');
        } finally {
            button.dataset.saving = 'false';
            button.disabled = false;
        }
    });
});



// ===== Unified signed-in user actions =====
document.addEventListener('DOMContentLoaded', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const loginUrl = document.querySelector('meta[name="login-url"]')?.content || '/auth/login';

    const jsonFetch = (url, options = {}) => fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.body ? {'Content-Type':'application/json','X-CSRF-TOKEN':csrf} : {}),
            ...(options.headers || {})
        }
    });

    const toast = message => {
        let el = document.querySelector('.save-toast');
        if (!el) {
            el = document.createElement('div');
            el.className = 'save-toast';
            document.body.appendChild(el);
        }
        el.textContent = message;
        el.classList.add('show');
        clearTimeout(window.__aiHubUserActionToast);
        window.__aiHubUserActionToast = setTimeout(() => el.classList.remove('show'), 2200);
    };

    const rememberInteraction = async payload => {
        const response = await jsonFetch('/user/interactions/intent', {
            method:'POST',
            body:JSON.stringify({...payload, return_to:window.location.href})
        });
        const data = await response.json().catch(() => ({}));
        window.location.assign(data.login_url || loginUrl);
    };

    const hydrateAction = async button => {
        const query = new URLSearchParams({
            action:button.dataset.userAction,
            target_type:button.dataset.targetType,
            target_id:button.dataset.targetId
        });
        try {
            const response = await jsonFetch('/user/interactions/status?' + query.toString());
            if (!response.ok) return;
            const data = await response.json();
            if (!data.authenticated) return;
            button.classList.toggle('active', Boolean(data.active));
            button.setAttribute('aria-pressed', data.active ? 'true' : 'false');
            const label = button.querySelector('[data-user-action-label]');
            if (label) {
                if (button.dataset.userAction === 'follow') label.textContent = data.active ? 'Following' : 'Follow';
                if (button.dataset.userAction === 'helpful') label.textContent = data.active ? 'Helpful' : 'Helpful?';
            }
        } catch (_) {}
    };

    const makeActionButton = (action, type, id, icon, label) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'user-action-btn';
        button.dataset.userAction = action;
        button.dataset.targetType = type;
        button.dataset.targetId = String(id);
        button.setAttribute('aria-pressed','false');
        button.innerHTML = `<i data-lucide="${icon}"></i><span data-user-action-label>${label}</span>`;
        return button;
    };

    // Follow button on Tool / Model / Company detail heroes.
    const detailSave = document.querySelector(
        '.tool-hero-actions [data-save-item], .model-detail-actions [data-save-item], .company-profile-actions [data-save-item]'
    );
    if (detailSave && ['tool','model','company'].includes(detailSave.dataset.saveType)) {
        const container = detailSave.parentElement;
        if (container && !container.querySelector('[data-user-action="follow"]')) {
            const follow = makeActionButton('follow', detailSave.dataset.saveType, detailSave.dataset.saveId, 'bell-plus', 'Follow');
            if (detailSave.className) follow.className += ' ' + detailSave.className;
            container.insertBefore(follow, detailSave.nextSibling);
            hydrateAction(follow);
        }
    }

    // Tool detail: always show Write Review. The route itself is auth protected.
    const toolSave = document.querySelector('.tool-hero-actions [data-save-item][data-save-type="tool"]');
    if (toolSave) {
        const container = toolSave.parentElement;
        if (container && !container.querySelector('[data-write-review]')) {
            const link = document.createElement('a');
            link.href = `/tools/${encodeURIComponent(toolSave.dataset.saveId)}/review`;
            link.dataset.writeReview = 'true';
            link.className = 'detail-secondary-btn';
            link.innerHTML = '<i data-lucide="star"></i><span>Write Review</span>';
            container.appendChild(link);
        }
    }

    // Review detail: Helpful vote.
    const reviewMatch = window.location.pathname.match(/^\/reviews\/(\d+)\/?$/);
    const reviewAside = document.querySelector('.review-reading-grid .article-aside');
    if (reviewMatch && reviewAside && !reviewAside.querySelector('[data-user-action="helpful"]')) {
        const helpful = makeActionButton('helpful', 'review', reviewMatch[1], 'thumbs-up', 'Helpful?');
        helpful.style.width = '100%';
        reviewAside.appendChild(helpful);
        hydrateAction(helpful);
    }

    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-user-action]');
        if (!button) return;

        event.preventDefault();
        if (button.disabled) return;
        button.disabled = true;

        const payload = {
            action:button.dataset.userAction,
            target_type:button.dataset.targetType,
            target_id:Number(button.dataset.targetId)
        };

        try {
            const response = await jsonFetch('/user/interactions/toggle', {
                method:'POST',
                body:JSON.stringify(payload)
            });

            if (response.status === 401) {
                await rememberInteraction(payload);
                return;
            }

            if (response.status === 403) {
                toast('Your account cannot perform this action.');
                return;
            }

            if (!response.ok) throw new Error();

            const data = await response.json();
            button.classList.toggle('active', Boolean(data.active));
            button.setAttribute('aria-pressed', data.active ? 'true' : 'false');
            const label = button.querySelector('[data-user-action-label]');
            if (label) {
                if (payload.action === 'follow') label.textContent = data.active ? 'Following' : 'Follow';
                if (payload.action === 'helpful') label.textContent = data.active ? 'Helpful' : 'Helpful?';
            }
            toast(data.message || 'Updated.');
        } catch (_) {
            toast('Could not update this action. Please try again.');
        } finally {
            button.disabled = false;
        }
    });

    // Save comparison button on both preview and published comparison pages.
    const detailActions = document.querySelector('.comparison-detail-hero .detail-actions');
    const detailStrip = document.querySelector('.comparison-detail-hero .detail-product-strip');
    if (detailActions && detailStrip && !detailActions.querySelector('[data-save-comparison]')) {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.saveComparison = 'true';
        button.innerHTML = '<i data-lucide="bookmark"></i><span data-compare-save-label>Save comparison</span>';
        detailActions.prepend(button);

        const historyLink = document.createElement('a');
        historyLink.href = '/my/comparisons';
        historyLink.innerHTML = '<i data-lucide="library"></i> My comparisons';
        detailActions.appendChild(historyLink);

        const payload = (() => {
            const pathMatch = window.location.pathname.match(/^\/compare\/([^\/]+)\/?$/);
            if (pathMatch && pathMatch[1] !== 'preview' && pathMatch[1] !== 'builder') {
                return {comparison_slug:decodeURIComponent(pathMatch[1])};
            }

            const params = new URLSearchParams(window.location.search);
            let ids = params.getAll('items[]');
            if (!ids.length) ids = params.getAll('items');
            if (!ids.length && params.get('items')) ids = params.get('items').split(',');

            return {
                type:params.get('type') || 'tool',
                item_ids:ids.map(Number).filter(Boolean),
                title:document.querySelector('.comparison-detail-hero h1')?.textContent?.trim() || ''
            };
        })();

        const hydrateComparison = async () => {
            try {
                const query = new URLSearchParams();
                if (payload.comparison_slug) query.set('comparison_slug', payload.comparison_slug);
                else {
                    query.set('type',payload.type);
                    payload.item_ids.forEach(id => query.append('item_ids[]',String(id)));
                    if (payload.title) query.set('title',payload.title);
                }
                const response = await jsonFetch('/user/comparisons/status?' + query.toString());
                if (!response.ok) return;
                const data = await response.json();
                if (!data.authenticated) return;
                button.classList.toggle('active',Boolean(data.saved));
                button.querySelector('[data-compare-save-label]').textContent = data.saved ? 'Saved' : 'Save comparison';
            } catch (_) {}
        };

        button.addEventListener('click', async () => {
            if (button.disabled) return;
            button.disabled = true;
            try {
                const response = await jsonFetch('/user/comparisons/toggle', {
                    method:'POST',
                    body:JSON.stringify(payload)
                });

                if (response.status === 401) {
                    const intent = await jsonFetch('/user/comparisons/intent', {
                        method:'POST',
                        body:JSON.stringify({...payload,return_to:window.location.href})
                    });
                    const data = await intent.json().catch(() => ({}));
                    window.location.assign(data.login_url || loginUrl);
                    return;
                }

                if (response.status === 403) {
                    toast('Your account cannot perform this action.');
                    return;
                }

                if (!response.ok) throw new Error();

                const data = await response.json();
                button.classList.toggle('active',Boolean(data.saved));
                button.querySelector('[data-compare-save-label]').textContent = data.saved ? 'Saved' : 'Save comparison';
                toast(data.message || 'Comparison updated.');
            } catch (_) {
                toast('Could not save this comparison.');
            } finally {
                button.disabled = false;
            }
        });

        hydrateComparison();
    }

    // Add personal-history links to directory toolbars without forcing login for public browsing.
    const labActions = document.querySelector('.lab-directory .lab-actions');
    if (labActions && !labActions.querySelector('[data-test-history-link]')) {
        const link = document.createElement('a');
        link.href = '/my/test-lab-history';
        link.dataset.testHistoryLink = 'true';
        link.className = 'user-action-btn';
        link.innerHTML = '<i data-lucide="history"></i> My history';
        labActions.appendChild(link);
    }

    const compareHeroActions = document.querySelector('.comparison-hero-actions');
    if (compareHeroActions && !compareHeroActions.querySelector('[data-my-comparisons-link]')) {
        const link = document.createElement('a');
        link.href = '/my/comparisons';
        link.dataset.myComparisonsLink = 'true';
        link.className = 'user-action-btn';
        link.innerHTML = '<i data-lucide="library"></i> My comparisons';
        compareHeroActions.appendChild(link);
    }

    if (window.lucide) window.lucide.createIcons();
});
