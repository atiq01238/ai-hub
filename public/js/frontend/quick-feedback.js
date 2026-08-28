(() => {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const jsonFetch = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.body ? {'Content-Type':'application/json', 'X-CSRF-TOKEN':csrf} : {}),
                ...(options.headers || {}),
            },
        });

        const data = await response.json().catch(() => ({}));
        return {response, data};
    };

    const toast = message => {
        let element = document.querySelector('.qf-toast');
        if (!element) {
            element = document.createElement('div');
            element.className = 'qf-toast';
            document.body.appendChild(element);
        }
        element.textContent = message;
        element.classList.add('show');
        clearTimeout(window.__quickFeedbackToast);
        window.__quickFeedbackToast = setTimeout(() => element.classList.remove('show'), 2400);
    };

    const setStatus = (card, message, state = '') => {
        const node = card.querySelector('[data-feedback-status]');
        if (!node) return;
        node.textContent = message;
        node.classList.remove('is-success', 'is-error');
        if (state) node.classList.add(`is-${state}`);
    };

    const rememberAndLogin = async payload => {
        const {response, data} = await jsonFetch('/feedback/intent', {
            method: 'POST',
            body: JSON.stringify({...payload, return_to: window.location.href}),
        });

        if (!response.ok || !data.login_url) {
            throw new Error(data.message || 'Could not continue to sign in.');
        }

        window.location.assign(data.login_url);
    };

    const updateRatingUi = (card, score, data = null) => {
        const stars = [...card.querySelectorAll('[data-feedback-score]')];
        stars.forEach(button => {
            const value = Number(button.dataset.feedbackScore);
            button.classList.toggle('active', value <= score);
            button.classList.remove('preview');
            button.setAttribute('aria-checked', value === score ? 'true' : 'false');
        });
        card.dataset.feedbackValue = String(score);

        if (data) {
            const average = card.querySelector('[data-feedback-average]');
            const count = card.querySelector('[data-feedback-count]');
            if (average) average.textContent = data.average == null ? '—' : Number(data.average).toFixed(1);
            if (count) count.textContent = String(data.count ?? 0);
        }
    };

    const updateVoteUi = (card, choice, data = null) => {
        card.querySelectorAll('[data-feedback-choice]').forEach(button => {
            button.classList.toggle('active', button.dataset.feedbackChoice === choice);
        });
        card.dataset.feedbackValue = choice;

        if (data?.counts) {
            const summary = card.querySelector('[data-feedback-summary]');
            if (summary) {
                summary.textContent = card.dataset.feedbackType === 'article'
                    ? `${Number(data.counts.helpful || 0)} helpful vote${Number(data.counts.helpful || 0) === 1 ? '' : 's'}`
                    : `${Number(data.counts.accurate || 0)} confirmations · ${Number(data.counts.outdated || 0)} outdated reports`;
            }
        }
    };

    document.querySelectorAll('[data-quick-feedback]').forEach(card => {
        if (card.dataset.feedbackKind !== 'rating') return;
        const stars = [...card.querySelectorAll('[data-feedback-score]')];
        const current = () => Number(card.dataset.feedbackValue || 0);

        stars.forEach(button => {
            button.addEventListener('mouseenter', () => {
                const hoverScore = Number(button.dataset.feedbackScore);
                stars.forEach(star => star.classList.toggle('preview', Number(star.dataset.feedbackScore) <= hoverScore));
            });
        });

        card.querySelector('.qf-stars')?.addEventListener('mouseleave', () => {
            stars.forEach(star => star.classList.remove('preview'));
            updateRatingUi(card, current());
        });
    });

    document.addEventListener('click', async event => {
        const scoreButton = event.target.closest('[data-feedback-score]');
        const voteButton = event.target.closest('[data-feedback-choice]');
        const button = scoreButton || voteButton;
        if (!button) return;

        const card = button.closest('[data-quick-feedback]');
        if (!card || button.disabled) return;

        event.preventDefault();

        const payload = {
            kind: card.dataset.feedbackKind,
            type: card.dataset.feedbackType,
            id: Number(card.dataset.feedbackId),
        };

        if (scoreButton) payload.score = Number(scoreButton.dataset.feedbackScore);
        if (voteButton) payload.choice = voteButton.dataset.feedbackChoice;

        const controls = scoreButton ? card.querySelector('.qf-stars') : card.querySelector('.qf-vote-controls');
        controls?.classList.add('is-busy');
        setStatus(card, 'Saving…');

        try {
            const {response, data} = await jsonFetch('/feedback', {
                method: 'POST',
                body: JSON.stringify(payload),
            });

            if (response.status === 401) {
                setStatus(card, 'Sign in to save your feedback.');
                await rememberAndLogin(payload);
                return;
            }

            if (response.status === 403) {
                throw new Error('Please verify your account before rating.');
            }

            if (!response.ok) {
                throw new Error(data.message || 'Could not save your feedback.');
            }

            if (scoreButton) {
                updateRatingUi(card, Number(payload.score), data);
                setStatus(card, `Your rating: ${payload.score}/5`, 'success');
            } else {
                updateVoteUi(card, payload.choice, data);
                setStatus(card, 'Your feedback is saved.', 'success');
            }

            toast(data.message || 'Feedback saved.');
        } catch (error) {
            setStatus(card, error.message || 'Could not save. Please try again.', 'error');
            toast(error.message || 'Could not save. Please try again.');
        } finally {
            controls?.classList.remove('is-busy');
        }
    });
})();
