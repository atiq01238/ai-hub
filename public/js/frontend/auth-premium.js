(() => {
    const eyeOpen = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.7-6.5 10-6.5S22 12 22 12s-3.7 6.5-10 6.5S2 12 2 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>';
    const eyeClosed = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.7 5.6c.4-.1.8-.1 1.3-.1 6.3 0 10 6.5 10 6.5a16 16 0 0 1-3 3.7M6.2 6.2C3.5 8 2 12 2 12s3.7 6.5 10 6.5c1.5 0 2.9-.4 4.1-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const inputId = button.getAttribute('data-password-toggle');
        const input = document.getElementById(inputId);
        if (!input) return;

        button.addEventListener('click', () => {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            button.innerHTML = isHidden ? eyeClosed : eyeOpen;
            button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            input.focus({ preventScroll: true });
        });
    });

    const password = document.querySelector('[data-strength-input]');
    const meter = document.querySelector('[data-strength-meter]');
    const label = document.querySelector('[data-strength-label]');
    if (password && meter && label) {
        const calculate = (value) => {
            if (!value) return 0;
            let score = 0;
            if (value.length >= 8) score++;
            if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score++;
            if (/\d/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value) || value.length >= 14) score++;
            return Math.min(score, 4);
        };
        const names = ['—', 'Basic', 'Fair', 'Good', 'Strong'];
        const update = () => {
            const score = calculate(password.value);
            meter.setAttribute('data-score', String(score));
            label.textContent = names[score];
        };
        password.addEventListener('input', update);
        update();
    }

    document.querySelectorAll('[data-auth-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const submit = form.querySelector('button[type="submit"]');
            if (!submit || submit.disabled) return;
            submit.disabled = true;
            const original = submit.textContent;
            submit.dataset.originalText = original;
            submit.textContent = submit.dataset.loadingText || 'Please wait…';
        });
    });
})();
