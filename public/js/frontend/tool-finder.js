(() => {
    const form = document.querySelector('[data-tool-finder-form]');
    if (!form) return;

    const task = form.querySelector('textarea[name="task"]');
    const submit = form.querySelector('[data-finder-submit]');
    const loading = form.querySelector('[data-finder-loading]');
    const taskWrap = form.querySelector('.finder-task-input-wrap');

    form.id = 'finder-start';

    form.addEventListener('submit', (event) => {
        const hasTask = task && task.value.trim().length >= 3;
        const hasShortcut = !!form.querySelector('input[name="shortcut"]:checked');

        if (!hasTask && !hasShortcut) {
            event.preventDefault();
            taskWrap?.classList.add('is-invalid');
            task?.focus();
            return;
        }

        taskWrap?.classList.remove('is-invalid');
        if (submit) {
            submit.disabled = true;
            const label = submit.querySelector('span');
            if (label) label.textContent = 'Finding your best matches...';
        }
        if (loading) loading.hidden = false;
    });

    task?.addEventListener('input', () => taskWrap?.classList.remove('is-invalid'));
    form.querySelectorAll('input[name="shortcut"]').forEach((input) => {
        input.addEventListener('change', () => taskWrap?.classList.remove('is-invalid'));
    });

    document.querySelectorAll('[data-finder-edit]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            form.scrollIntoView({behavior: 'smooth', block: 'start'});
            window.setTimeout(() => task?.focus({preventScroll: true}), 350);
        });
    });

    const results = document.querySelector('[data-finder-results]');
    if (results && window.matchMedia('(max-width: 760px)').matches) {
        window.setTimeout(() => results.scrollIntoView({behavior: 'smooth', block: 'start'}), 120);
    }


    if (results) {
        const eventId = results.dataset.finderEventId;
        const clickUrl = results.dataset.finderClickUrl;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

        if (eventId && clickUrl && csrf) {
            results.querySelectorAll('[data-finder-result-link]').forEach((link) => {
                link.addEventListener('click', () => {
                    const toolId = link.dataset.toolId;
                    const action = link.dataset.finderAction;
                    if (!toolId || !action) return;

                    const payload = new FormData();
                    payload.append('_token', csrf);
                    payload.append('event_id', eventId);
                    payload.append('tool_id', toolId);
                    payload.append('action', action);

                    if (navigator.sendBeacon) {
                        navigator.sendBeacon(clickUrl, payload);
                        return;
                    }

                    fetch(clickUrl, {
                        method: 'POST',
                        headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                        body: payload,
                        keepalive: true,
                        credentials: 'same-origin',
                    }).catch(() => {});
                });
            });
        }
    }

    if (window.lucide?.createIcons) window.lucide.createIcons();
})();
