(() => {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const state = { context: null, authenticated: false };

    const api = async (url, options = {}) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.body ? {'Content-Type':'application/json','X-CSRF-TOKEN':csrf} : {}),
                ...(options.headers || {}),
            },
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const error = new Error(data.message || 'Request failed.');
            error.status = response.status;
            error.data = data;
            throw error;
        }

        return data;
    };

    const escapeHtml = value => String(value ?? '')
        .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
        .replaceAll('"','&quot;').replaceAll("'","&#039;");

    const toast = message => {
        let el = document.querySelector('.community-toast');
        if (!el) {
            el = document.createElement('div');
            el.className = 'community-toast';
            document.body.appendChild(el);
        }
        el.textContent = message;
        el.classList.add('show');
        clearTimeout(window.__communityToast);
        window.__communityToast = setTimeout(() => el.classList.remove('show'), 2200);
    };

    const signIn = () => {
        const returnTo = encodeURIComponent(window.location.href);
        window.location.assign(`/community/login?return_to=${returnTo}`);
    };

    const icon = name => `<i data-lucide="${name}"></i>`;

    const mountPointForComments = type => {
        if (document.querySelector('[data-community-static]')) {
            return document.querySelector('[data-community-static]');
        }

        if (type === 'news') return document.querySelector('.news-article');
        if (type === 'article') return document.querySelector('.article-body');
        if (type === 'comparison') return document.querySelector('.comparison-detail-body .compare-container');
        if (type === 'test') return document.querySelector('.lab-detail-grid main');

        return null;
    };

    const mountPointForReviews = type => {
        if (type === 'tool') {
            return document.querySelector('.tool-detail-main');
        }

        if (type === 'model') {
            return document.querySelector('.model-detail-body main');
        }

        return null;
    };

    const ensureReviewButton = context => {
        const actions = context.type === 'tool'
            ? document.querySelector('.tool-hero-actions')
            : document.querySelector('.model-detail-actions');

        if (!actions || actions.querySelector('[data-community-review-cta], [data-write-review]')) return;

        const link = document.createElement('a');
        link.dataset.communityReviewCta = '1';
        link.className = context.type === 'tool' ? 'detail-secondary-btn' : '';
        link.href = context.type === 'tool'
            ? `/tools/${context.id}/review`
            : `/models/${context.id}/review`;
        link.innerHTML = `${icon('star')}<span>Write Review</span>`;
        actions.appendChild(link);
    };

    const renderReviews = async context => {
        ensureReviewButton(context);

        const mount = mountPointForReviews(context.type);
        if (!mount) return;

        let zone = document.querySelector('#community-reviews');

        if (!zone && context.type === 'tool') {
            const existing = document.querySelector('#reviews');
            if (existing) {
                zone = document.createElement('div');
                zone.id = 'community-reviews';
                existing.appendChild(zone);
            }
        }

        if (!zone) {
            zone = document.createElement('section');
            zone.id = 'community-reviews';
            zone.className = 'community-zone';
            mount.appendChild(zone);
        } else {
            zone.classList.add('community-zone');
        }

        const data = await api(`/community/reviews?type=${encodeURIComponent(context.type)}&id=${context.id}`);

        zone.innerHTML = `
            <div class="community-head">
                <div>
                    <span class="community-kicker">${icon('star')} COMMUNITY REVIEWS</span>
                    <h2>What AI Hub users say</h2>
                    <p>First-hand ratings are moderated before publication.</p>
                </div>
                <div class="community-summary">
                    <div><strong>${data.average ? data.average.toFixed(1) : '—'}</strong><span>${data.count} published review${data.count === 1 ? '' : 's'}</span></div>
                    <a class="community-review-cta" href="${context.type === 'tool' ? `/tools/${context.id}/review` : `/models/${context.id}/review`}">${icon('star')} ${data.authenticated ? 'Write / edit review' : 'Sign in to review'}</a>
                </div>
            </div>
            <div class="community-review-list">
                ${data.reviews.length ? data.reviews.map(renderReviewCard).join('') : `
                    <div class="community-empty">${icon('message-square-plus')}<strong>No community reviews yet.</strong><span>Be the first person to share a useful experience after signing in.</span></div>
                `}
            </div>
        `;

        refreshIcons();
    };

    const renderReviewCard = review => `
        <article class="community-review-card" data-review-id="${review.id}">
            <div class="community-review-top">
                <div class="community-review-user">
                    <span class="community-avatar">${escapeHtml(review.user.initial)}</span>
                    <div><strong>${escapeHtml(review.user.name)}</strong><small>${escapeHtml(review.created_human || '')}</small></div>
                </div>
                <span class="community-stars">★ ${Number(review.rating).toFixed(1)}</span>
            </div>
            <p>${escapeHtml(review.body || 'Rating submitted without a written review.')}</p>
            <div class="community-review-actions">
                <button type="button" data-helpful-type="review" data-helpful-id="${review.id}" class="${review.helpful ? 'active' : ''}">Helpful <span>${review.helpful_count || 0}</span></button>
                <button type="button" data-report-type="review" data-report-id="${review.id}">Report</button>
            </div>
        </article>
    `;

    const renderComment = comment => `
        <article class="community-comment" data-comment-id="${comment.id}">
            <div class="community-comment-main">
                <span class="community-avatar">${escapeHtml(comment.user.initial)}</span>
                <div>
                    <div class="community-comment-meta">
                        <strong>${escapeHtml(comment.user.name)}</strong>
                        <time>${escapeHtml(comment.created_human || '')}</time>
                        ${comment.status !== 'published' ? `<span class="community-pending">${escapeHtml(comment.status)}</span>` : ''}
                    </div>
                    <p class="community-body">${escapeHtml(comment.body)}</p>
                    <div class="community-actions">
                        ${comment.status === 'published' ? `<button type="button" data-helpful-type="comment" data-helpful-id="${comment.id}" class="${comment.helpful ? 'active' : ''}">Helpful ${comment.helpful_count ? `(${comment.helpful_count})` : ''}</button>` : ''}
                        ${state.authenticated && comment.status === 'published' ? `<button type="button" data-reply-comment="${comment.id}">Reply</button>` : ''}
                        ${comment.mine ? `<button type="button" data-edit-comment="${comment.id}">Edit</button><button type="button" data-delete-comment="${comment.id}">Delete</button>` : ''}
                        ${!comment.mine && comment.status === 'published' ? `<button type="button" class="report" data-report-type="comment" data-report-id="${comment.id}">Report</button>` : ''}
                    </div>
                </div>
            </div>
            ${comment.replies?.length ? `<div class="community-replies">${comment.replies.map(renderComment).join('')}</div>` : ''}
        </article>
    `;

    const renderThread = async context => {
        const mount = mountPointForComments(context.type);
        if (!mount) return;

        let host = document.querySelector('[data-community-static]');
        if (!host) {
            host = document.createElement('div');
            host.dataset.communityStatic = '1';
            mount.appendChild(host);
        }

        host.innerHTML = `<section class="community-zone"><div class="community-empty">${icon('loader-circle')}<strong>Loading discussion…</strong></div></section>`;
        refreshIcons();

        const data = await api(`/community/comments?type=${encodeURIComponent(context.type)}&id=${context.id}`);
        state.authenticated = data.authenticated;

        host.innerHTML = `
            <section class="community-zone" id="community-discussion">
                <div class="community-head">
                    <div>
                        <span class="community-kicker">${icon('messages-square')} COMMUNITY DISCUSSION</span>
                        <h2>Discuss this ${escapeHtml(context.type)}</h2>
                        <p>Keep the conversation useful, specific and respectful.</p>
                    </div>
                    <div class="community-summary"><div><strong>${data.count}</strong><span>published comment${data.count === 1 ? '' : 's'}</span></div></div>
                </div>

                ${state.authenticated ? `
                    <form class="community-compose" data-community-compose>
                        <textarea name="body" maxlength="3000" required placeholder="Share a useful thought, question or real-world observation…"></textarea>
                        <div class="community-compose-foot">
                            <span>New or risky posts may be moderated · trusted clean posts can publish instantly · 3,000 character limit</span>
                            <button type="submit" class="primary">${icon('send')} Post comment</button>
                        </div>
                    </form>
                ` : `
                    <div class="community-compose">
                        <div class="community-compose-foot">
                            <span>Sign in to comment, reply, mark helpful or report community content.</span>
                            <button type="button" class="primary" data-community-signin>${icon('log-in')} Sign in to join discussion</button>
                        </div>
                    </div>
                `}

                <div class="community-list">
                    ${data.comments.length ? data.comments.map(renderComment).join('') : `
                        <div class="community-empty">${icon('message-square-plus')}<strong>No published comments yet.</strong><span>Start a thoughtful discussion about this page.</span></div>
                    `}
                </div>
            </section>
        `;

        refreshIcons();
    };

    const submitComment = async (body, parentId = null) => {
        if (!state.context) return;
        if (!state.authenticated) return signIn();

        const result = await api('/community/comments', {
            method:'POST',
            body:JSON.stringify({
                type:state.context.type,
                id:state.context.id,
                parent_id:parentId,
                body,
            }),
        });

        toast(result.message || 'Comment submitted.');
        await renderThread(state.context);
    };

    const editComment = async (id, currentBody) => {
        const next = window.prompt('Edit your comment:', currentBody);
        if (next === null || !next.trim()) return;

        const result = await api(`/community/comments/${id}`, {
            method:'PATCH',
            body:JSON.stringify({body:next.trim()}),
        });

        toast(result.message || 'Comment updated.');
        await renderThread(state.context);
    };

    const deleteComment = async id => {
        if (!window.confirm('Delete this comment?')) return;

        await api(`/community/comments/${id}`, {method:'DELETE'});
        toast('Comment deleted.');
        await renderThread(state.context);
    };

    const helpful = async (type, id, button) => {
        if (!state.authenticated) return signIn();

        const data = await api('/community/helpful', {
            method:'POST',
            body:JSON.stringify({type, id:Number(id)}),
        });

        button?.classList.toggle('active', Boolean(data.active));
        const span = button?.querySelector('span');
        if (span) span.textContent = data.count;
        toast(data.message);
    };

    const report = async (type, id) => {
        if (!state.authenticated) return signIn();

        const reason = window.prompt('Report reason: spam, harassment, misinformation, off_topic, or other', 'spam');
        if (!reason) return;

        const normalized = reason.trim().toLowerCase().replaceAll(' ', '_');
        if (!['spam','harassment','misinformation','off_topic','other'].includes(normalized)) {
            toast('Use one of the listed report reasons.');
            return;
        }

        const description = window.prompt('Optional details for the moderator:', '') ?? '';

        const data = await api('/community/report', {
            method:'POST',
            body:JSON.stringify({type,id:Number(id),reason:normalized,description}),
        });

        toast(data.message);
    };

    const refreshIcons = () => window.lucide?.createIcons?.();

    const initStaticBenchmark = async () => {
        const staticHost = document.querySelector('[data-community-static][data-community-type]');
        if (!staticHost) return false;

        state.context = {
            type:staticHost.dataset.communityType,
            id:Number(staticHost.dataset.communityId),
            name:document.querySelector('h1')?.textContent?.trim() || 'Benchmark',
            mode:'comment',
        };

        await renderThread(state.context);
        return true;
    };

    const init = async () => {
        try {
            if (await initStaticBenchmark()) return;

            const data = await api(`/community/context?path=${encodeURIComponent(window.location.pathname)}`);
            state.authenticated = data.authenticated;

            if (!data.context) return;

            state.context = data.context;

            if (data.context.mode === 'review') {
                await renderReviews(data.context);
            } else {
                await renderThread(data.context);
            }
        } catch (error) {
            console.error('AI Hub community init failed', error);
        }
    };

    document.addEventListener('submit', async event => {
        const form = event.target.closest('[data-community-compose]');
        if (!form) return;

        event.preventDefault();
        const body = form.querySelector('textarea[name="body"]')?.value?.trim();
        if (!body) return;

        const button = form.querySelector('button[type="submit"]');
        if (button) button.disabled = true;

        try {
            await submitComment(body);
        } catch (error) {
            if (error.status === 401) signIn();
            else toast(error.data?.message || 'Could not submit comment.');
        } finally {
            if (button) button.disabled = false;
        }
    });

    document.addEventListener('click', async event => {
        const signin = event.target.closest('[data-community-signin]');
        if (signin) return signIn();

        const helpfulBtn = event.target.closest('[data-helpful-type]');
        if (helpfulBtn) {
            try {
                await helpful(helpfulBtn.dataset.helpfulType, helpfulBtn.dataset.helpfulId, helpfulBtn);
            } catch (error) {
                if (error.status === 401) signIn();
                else toast('Could not update helpful vote.');
            }
            return;
        }

        const reportBtn = event.target.closest('[data-report-type]');
        if (reportBtn) {
            try {
                await report(reportBtn.dataset.reportType, reportBtn.dataset.reportId);
            } catch (error) {
                if (error.status === 401) signIn();
                else toast('Could not submit report.');
            }
            return;
        }

        const replyBtn = event.target.closest('[data-reply-comment]');
        if (replyBtn) {
            if (!state.authenticated) return signIn();

            const id = Number(replyBtn.dataset.replyComment);
            const row = replyBtn.closest('.community-comment');
            if (!row) return;

            row.querySelector(':scope > .community-reply-form')?.remove();

            const form = document.createElement('form');
            form.className = 'community-reply-form';
            form.innerHTML = `
                <textarea maxlength="3000" required placeholder="Write a reply…"></textarea>
                <div class="community-form-actions">
                    <button type="button" class="community-inline-btn" data-cancel-inline>Cancel</button>
                    <button type="submit" class="community-inline-btn">Reply</button>
                </div>
            `;
            row.appendChild(form);
            form.querySelector('textarea').focus();

            form.addEventListener('submit', async e => {
                e.preventDefault();
                const body = form.querySelector('textarea').value.trim();
                if (!body) return;
                try {
                    await submitComment(body, id);
                } catch (error) {
                    toast(error.data?.message || 'Could not submit reply.');
                }
            }, {once:true});
            return;
        }

        const cancel = event.target.closest('[data-cancel-inline]');
        if (cancel) {
            cancel.closest('.community-reply-form,.community-edit-form')?.remove();
            return;
        }

        const editBtn = event.target.closest('[data-edit-comment]');
        if (editBtn) {
            const row = editBtn.closest('.community-comment');
            const body = row?.querySelector('.community-body')?.textContent || '';
            try { await editComment(Number(editBtn.dataset.editComment), body); }
            catch (error) { toast(error.data?.message || 'Could not edit comment.'); }
            return;
        }

        const deleteBtn = event.target.closest('[data-delete-comment]');
        if (deleteBtn) {
            try { await deleteComment(Number(deleteBtn.dataset.deleteComment)); }
            catch (error) { toast(error.data?.message || 'Could not delete comment.'); }
        }
    });

    document.addEventListener('DOMContentLoaded', init);
})();
