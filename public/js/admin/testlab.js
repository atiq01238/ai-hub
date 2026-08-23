document.addEventListener('DOMContentLoaded', () => {
    const initPicker = picker => {
        const scope = picker.closest('form') || document;
        const search = scope.querySelector('[data-model-search]');
        const count = scope.querySelector('[data-model-count]');
        const max = Number(picker.dataset.max || 6);
        const boxes = [...picker.querySelectorAll('input[type="checkbox"]')];

        const refresh = () => {
            const selected = boxes.filter(box => box.checked).length;
            if (count) count.textContent = `${selected} selected · max ${max}`;
            boxes.forEach(box => {
                const disabled = !box.checked && selected >= max;
                box.disabled = disabled;
                box.closest('label')?.classList.toggle('is-disabled', disabled);
            });
        };

        boxes.forEach(box => box.addEventListener('change', refresh));
        search?.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();
            picker.querySelectorAll('[data-model-option]').forEach(option => {
                option.hidden = q !== '' && !String(option.dataset.name || '').includes(q);
            });
        });
        refresh();
    };

    document.querySelectorAll('[data-model-picker]').forEach(initPicker);

    document.querySelectorAll('[data-weight-grid]').forEach(grid => {
        const host = grid.closest('form') || document;
        const totals = [...host.querySelectorAll('[data-weight-total]')];
        const inputs = [...grid.querySelectorAll('input[type="number"]')];
        const refresh = () => {
            const sum = inputs.reduce((n, input) => n + (Number(input.value) || 0), 0);
            totals.forEach(total => {
                total.textContent = `${sum}%`;
                total.closest('.tl-weight-total')?.classList.toggle('is-invalid', sum !== 100);
                total.style.color = sum === 100 ? '' : '#ff7185';
            });
        };
        inputs.forEach(input => input.addEventListener('input', refresh));
        refresh();
    });

    const flashCopyState = button => {
        const original = button.innerHTML;
        button.innerHTML = '<i data-lucide="check"></i>Copied';
        button.disabled = true;
        if (window.lucide) window.lucide.createIcons();
        window.setTimeout(() => {
            button.innerHTML = original;
            button.disabled = false;
            if (window.lucide) window.lucide.createIcons();
        }, 1400);
    };

    document.querySelectorAll('[data-copy-prompt]').forEach(button => {
        button.addEventListener('click', async () => {
            const selector = button.dataset.copySource;
            const source = selector
                ? document.querySelector(selector)
                : button.closest('form, .tl-wizard-panel')?.querySelector('[data-shared-prompt]');
            const text = source?.value ?? source?.textContent ?? '';
            if (!text.trim()) return;

            try {
                await navigator.clipboard.writeText(text);
                flashCopyState(button);
            } catch (_) {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                textarea.remove();
                flashCopyState(button);
            }
        });
    });
});
