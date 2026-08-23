document.addEventListener('DOMContentLoaded', () => {
    const filters = document.querySelector('[data-lab-filters]');
    const overlay = document.querySelector('[data-lab-overlay]');
    const toggle = open => {
        filters?.classList.toggle('open', open);
        overlay?.classList.toggle('open', open);
        document.body.classList.toggle('lab-filter-lock', open);
    };
    document.querySelector('[data-lab-filter-open]')?.addEventListener('click', () => toggle(true));
    document.querySelector('[data-lab-filter-close]')?.addEventListener('click', () => toggle(false));
    overlay?.addEventListener('click', () => toggle(false));

    document.querySelector('[data-copy-prompt]')?.addEventListener('click', async event => {
        const text = document.querySelector('[data-prompt-text]')?.textContent || '';
        if (!text) return;
        try {
            await navigator.clipboard.writeText(text.trim());
            const button = event.currentTarget;
            const original = button.innerHTML;
            button.innerHTML = 'Copied';
            setTimeout(() => { button.innerHTML = original; if (window.lucide) window.lucide.createIcons(); }, 1200);
        } catch (_) {}
    });
});
