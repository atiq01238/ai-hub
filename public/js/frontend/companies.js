document.addEventListener('DOMContentLoaded', () => {
    const filters = document.querySelector('[data-company-filters]');
    const overlay = document.querySelector('[data-company-overlay]');
    const open = document.querySelector('[data-company-filter-open]');
    const close = document.querySelector('[data-company-filter-close]');
    const setOpen = value => {
        filters?.classList.toggle('open', value);
        overlay?.classList.toggle('open', value);
        document.body.style.overflow = value ? 'hidden' : '';
    };
    open?.addEventListener('click', () => setOpen(true));
    close?.addEventListener('click', () => setOpen(false));
    overlay?.addEventListener('click', () => setOpen(false));
});
