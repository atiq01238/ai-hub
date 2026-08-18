(() => {
    const toggle = document.querySelector('[data-news-filter-toggle]');
    const filters = document.querySelector('[data-news-filters]');
    if (toggle && filters) {
        toggle.addEventListener('click', () => {
            filters.classList.toggle('open');
            toggle.classList.toggle('active');
        });
    }

    const copyButton = document.querySelector('[data-copy-url]');
    if (copyButton) {
        copyButton.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(window.location.href);
                const original = copyButton.innerHTML;
                copyButton.innerHTML = '<span>Copied!</span>';
                setTimeout(() => { copyButton.innerHTML = original; if (window.lucide) window.lucide.createIcons(); }, 1400);
            } catch (_) {
                window.prompt('Copy this link:', window.location.href);
            }
        });
    }
})();
