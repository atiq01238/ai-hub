(() => {
    const nav = document.querySelector('[data-detail-nav]');
    const links = [...document.querySelectorAll('.detail-nav-links a')];
    const sections = links.map(link => document.querySelector(link.getAttribute('href'))).filter(Boolean);
    const saveButton = document.querySelector('[data-save-tool]');

    const setActive = () => {
        if (!nav || !sections.length) return;
        const offset = nav.offsetHeight + 100;
        let active = sections[0];
        for (const section of sections) {
            if (section.getBoundingClientRect().top <= offset) active = section;
        }
        links.forEach(link => link.classList.toggle('active', link.getAttribute('href') === `#${active.id}`));
    };

    window.addEventListener('scroll', setActive, { passive: true });
    setActive();

    links.forEach(link => link.addEventListener('click', () => {
        links.forEach(item => item.classList.remove('active'));
        link.classList.add('active');
    }));

    if (saveButton) {
        saveButton.addEventListener('click', () => {
            saveButton.classList.toggle('saved');
            const saved = saveButton.classList.contains('saved');
            const span = saveButton.querySelector('span');
            if (span) span.textContent = saved ? 'Saved' : 'Save';
            const icon = saveButton.querySelector('svg');
            if (icon) icon.style.fill = saved ? 'currentColor' : 'none';
        });
    }
})();
