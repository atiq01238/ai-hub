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

// Test Lab V3 — dynamic rubric + explicit N/A handling.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-rubric-builder]').forEach(builder => {
        const total = builder.querySelector('[data-rubric-total]');
        const rows = [...builder.querySelectorAll('[data-rubric-item]')];
        const locked = builder.dataset.rubricLocked === '1';

        const refresh = () => {
            let sum = 0;
            rows.forEach(row => {
                const toggle = row.querySelector('[data-rubric-toggle]');
                const weight = row.querySelector('[data-rubric-weight]');
                const enabled = Boolean(toggle?.checked);
                row.classList.toggle('is-enabled', enabled);
                if (weight) {
                    if (!locked) weight.disabled = !enabled;
                    if (enabled) sum += Number(weight.value || 0);
                }
            });
            if (total) {
                total.textContent = `${sum}%`;
                total.classList.toggle('is-invalid', sum !== 100);
            }
        };

        rows.forEach(row => {
            row.querySelector('[data-rubric-toggle]')?.addEventListener('change', refresh);
            row.querySelector('[data-rubric-weight]')?.addEventListener('input', refresh);
        });
        refresh();
    });

    document.querySelectorAll('.tl-score-criterion').forEach(row => {
        const input = row.querySelector('[data-score-input]');
        const na = row.querySelector('[data-score-na]');
        const refresh = () => {
            if (!input || !na) return;
            input.disabled = na.checked;
            row.classList.toggle('is-na', na.checked);
        };
        na?.addEventListener('change', refresh);
        refresh();
    });
});
