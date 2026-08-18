(() => {
    const panel = document.querySelector('[data-content-filters]');
    const overlay = document.querySelector('[data-content-overlay]');
    const open = document.querySelector('[data-content-filter-open]');
    const close = document.querySelector('[data-content-filter-close]');
    if (!panel) return;
    const setOpen = state => {
        panel.classList.toggle('open', state);
        overlay?.classList.toggle('open', state);
        document.body.style.overflow = state ? 'hidden' : '';
    };
    open?.addEventListener('click', () => setOpen(true));
    close?.addEventListener('click', () => setOpen(false));
    overlay?.addEventListener('click', () => setOpen(false));
    window.addEventListener('resize', () => { if (window.innerWidth > 900) setOpen(false); });
})();
