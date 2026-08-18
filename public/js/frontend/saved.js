document.addEventListener('DOMContentLoaded', () => {
    const toggleUrl = document.querySelector('meta[name="saved-toggle-url"]')?.content;
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
        window.__aiHubSaveToast = setTimeout(() => toast.classList.remove('show'), 2200);
    };

    const applyState = (button, saved) => {
        button.classList.toggle('saved', saved);
        button.setAttribute('aria-pressed', saved ? 'true' : 'false');
        const label = button.querySelector('[data-save-label]');
        if (label) label.textContent = saved ? 'Saved' : (label.dataset.defaultLabel || 'Save');
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
            const response = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) return;
            const data = await response.json();
            if (!data.authenticated) return;
            const savedIds = new Set((data.saved_ids || []).map(String));
            buttons.filter(button => button.dataset.saveType === type).forEach(button => applyState(button, savedIds.has(String(button.dataset.saveId))));
        } catch (_) {}
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
                body: JSON.stringify({ type: button.dataset.saveType, id: Number(button.dataset.saveId) })
            });

            if (response.status === 401 || response.status === 419) {
                window.location.href = loginUrl;
                return;
            }
            if (!response.ok) throw new Error('save-failed');

            const data = await response.json();
            document.querySelectorAll(`[data-save-item][data-save-type="${button.dataset.saveType}"][data-save-id="${button.dataset.saveId}"]`).forEach(match => applyState(match, Boolean(data.saved)));
            showToast(data.message || (data.saved ? 'Saved.' : 'Removed.'));

            if (!data.saved && button.dataset.removeCard === 'true') {
                const card = button.closest('[data-saved-card]');
                if (card) {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(.98)';
                    setTimeout(() => {
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
