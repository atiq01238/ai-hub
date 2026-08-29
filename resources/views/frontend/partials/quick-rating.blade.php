@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/frontend/quick-feedback.css') }}?v=20260829-qf3">
    @endpush
    @push('scripts')
        <script src="{{ asset('js/frontend/quick-feedback.js') }}?v=20260829-qf3"></script>
    @endpush
@endonce

@php
    $ratingAverage = data_get($summary, 'average');
    $ratingCount = (int) data_get($summary, 'count', 0);
    $viewerScore = data_get($summary, 'viewer_score');
@endphp
<div class="qf-card qf-rating-card"
     data-quick-feedback
     data-feedback-kind="rating"
     data-feedback-type="{{ $type }}"
     data-feedback-id="{{ $id }}"
     data-feedback-value="{{ $viewerScore }}">
    <div class="qf-copy">
        <span class="qf-eyebrow"><i data-lucide="star"></i> COMMUNITY RATING</span>
        <strong>{{ $label ?? 'Rate this item' }}</strong>
        <small>
            <b data-feedback-average>{{ $ratingAverage !== null ? number_format((float) $ratingAverage, 1) : '—' }}</b>/5
            · <span data-feedback-count>{{ $ratingCount }}</span> {{ \Illuminate\Support\Str::plural('rating', $ratingCount) }}
        </small>
    </div>
    <div class="qf-rating-controls">
        <div class="qf-stars" role="radiogroup" aria-label="Rate from 1 to 5 stars">
            @foreach(range(1, 5) as $score)
                <button type="button"
                        data-feedback-score="{{ $score }}"
                        class="{{ $viewerScore !== null && $score <= (float) $viewerScore ? 'active' : '' }}"
                        aria-label="{{ $score }} star{{ $score === 1 ? '' : 's' }}"
                        aria-checked="{{ $viewerScore !== null && (float) $viewerScore === (float) $score ? 'true' : 'false' }}"
                        role="radio">★</button>
            @endforeach
        </div>
        <span class="qf-status" data-feedback-status>
            {{ $viewerScore !== null ? 'Your rating: '.number_format((float) $viewerScore, 0).'/5' : 'Tap a star to rate instantly' }}
        </span>
    </div>
</div>
