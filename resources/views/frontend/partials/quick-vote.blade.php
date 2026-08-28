@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/frontend/quick-feedback.css') }}?v=20260828-qf1">
    @endpush
    @push('scripts')
        <script src="{{ asset('js/frontend/quick-feedback.js') }}?v=20260828-qf1"></script>
    @endpush
@endonce

@php
    $viewerChoice = data_get($summary, 'viewer_choice');
    $counts = collect(data_get($summary, 'counts', []));
    $isArticleFeedback = $type === 'article';
@endphp
<div class="qf-card qf-vote-card"
     data-quick-feedback
     data-feedback-kind="vote"
     data-feedback-type="{{ $type }}"
     data-feedback-id="{{ $id }}"
     data-feedback-value="{{ $viewerChoice }}">
    <div class="qf-copy">
        <span class="qf-eyebrow"><i data-lucide="{{ $isArticleFeedback ? 'message-circle-question' : 'badge-check' }}"></i> {{ $isArticleFeedback ? 'ARTICLE FEEDBACK' : 'COMMUNITY PRICE CHECK' }}</span>
        <strong>{{ $label }}</strong>
        <small data-feedback-summary>
            @if($isArticleFeedback)
                {{ (int) $counts->get('helpful', 0) }} helpful {{ \Illuminate\Support\Str::plural('vote', (int) $counts->get('helpful', 0)) }}
            @else
                {{ (int) $counts->get('accurate', 0) }} confirmations · {{ (int) $counts->get('outdated', 0) }} outdated reports
            @endif
        </small>
    </div>
    <div class="qf-vote-controls">
        @if($isArticleFeedback)
            <button type="button" data-feedback-choice="helpful" class="{{ $viewerChoice === 'helpful' ? 'active' : '' }}"><i data-lucide="thumbs-up"></i> Yes, helpful</button>
            <button type="button" data-feedback-choice="not_helpful" class="{{ $viewerChoice === 'not_helpful' ? 'active' : '' }}"><i data-lucide="thumbs-down"></i> Not really</button>
        @else
            <button type="button" data-feedback-choice="accurate" class="{{ $viewerChoice === 'accurate' ? 'active' : '' }}"><i data-lucide="check-circle-2"></i> Looks accurate</button>
            <button type="button" data-feedback-choice="outdated" class="{{ $viewerChoice === 'outdated' ? 'active' : '' }}"><i data-lucide="flag"></i> Price looks outdated</button>
        @endif
        <span class="qf-status" data-feedback-status>{{ $viewerChoice ? 'Your feedback is saved.' : 'One click · you can change it anytime' }}</span>
    </div>
</div>
