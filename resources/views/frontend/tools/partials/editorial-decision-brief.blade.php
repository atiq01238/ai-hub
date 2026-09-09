@php
    $briefBestFor = collect($editorialBrief['best_for'] ?? []);
    $briefStrengths = collect($editorialBrief['strengths'] ?? []);
    $briefConsiderations = collect($editorialBrief['considerations'] ?? []);
    $briefSignals = collect($editorialBrief['signals'] ?? [])->where('meaningful', true)->values();
    $briefAlternatives = collect($editorialBrief['alternatives'] ?? []);
    $briefVerdict = trim((string) ($editorialBrief['verdict'] ?? ''));
@endphp

<section class="detail-panel decision-brief-panel" id="decision">
    <div class="detail-section-head">
        <div>
            <span>Decision intelligence</span>
            <h2>AI Orbit decision brief</h2>
            <p>A compact reading of the recorded evidence, editorial review signals and comparison data for {{ $tool->name }}.</p>
        </div>
        <i data-lucide="waypoints"></i>
    </div>

    @if($briefVerdict !== '')
        <div class="decision-verdict">
            <span><i data-lucide="quote"></i>Editorial verdict</span>
            <p>{{ $briefVerdict }}</p>
        </div>
    @endif

    <div class="decision-brief-grid">
        @if($briefBestFor->isNotEmpty())
            <article class="decision-brief-card decision-brief-card--fit">
                <div class="decision-brief-card__head">
                    <span><i data-lucide="target"></i></span>
                    <div><small>Best fit</small><h3>Use cases mapped to this tool</h3></div>
                </div>
                <div class="decision-fit-list">
                    @foreach($briefBestFor as $item)
                        <div>
                            <div>
                                <a href="{{ route('use-cases.show', $item['slug']) }}">{{ $item['name'] }}</a>
                                @if($item['verified'])
                                    <span class="decision-proof decision-proof--verified"><i data-lucide="badge-check"></i>Verified fit</span>
                                @else
                                    <span class="decision-proof"><i data-lucide="tags"></i>Catalog mapping</span>
                                @endif
                            </div>
                            @if($item['note'])<p>{{ $item['note'] }}</p>@endif
                            @if($item['verified'] && $item['source_url'])
                                <a class="decision-source-link" href="{{ $item['source_url'] }}" target="_blank" rel="noopener noreferrer nofollow">Evidence source <i data-lucide="arrow-up-right"></i></a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </article>
        @endif

        @if($briefStrengths->isNotEmpty())
            <article class="decision-brief-card decision-brief-card--strengths">
                <div class="decision-brief-card__head">
                    <span><i data-lucide="sparkles"></i></span>
                    <div><small>Why it stands out</small><h3>Recorded strengths</h3></div>
                </div>
                <div class="decision-point-list">
                    @foreach($briefStrengths as $item)
                        <div>
                            <span class="decision-point-icon"><i data-lucide="check"></i></span>
                            <div>
                                <strong>{{ $item['title'] }}</strong>
                                <p>{{ $item['detail'] }}</p>
                                <span class="decision-proof {{ $item['verified'] ? 'decision-proof--verified' : '' }}">
                                    <i data-lucide="{{ $item['verified'] ? 'badge-check' : 'notebook-tabs' }}"></i>
                                    {{ $item['verified'] ? 'Source-backed capability' : 'Editorial review' }}
                                </span>
                                @if($item['source_url'])
                                    <a class="decision-source-link" href="{{ $item['source_url'] }}" target="_blank" rel="noopener noreferrer nofollow">Source <i data-lucide="arrow-up-right"></i></a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        @endif

        @if($briefConsiderations->isNotEmpty())
            <article class="decision-brief-card decision-brief-card--considerations">
                <div class="decision-brief-card__head">
                    <span><i data-lucide="circle-alert"></i></span>
                    <div><small>Before you choose</small><h3>Considerations & evidence gaps</h3></div>
                </div>
                <div class="decision-point-list">
                    @foreach($briefConsiderations as $item)
                        <div>
                            <span class="decision-point-icon"><i data-lucide="{{ $item['kind'] === 'editorial' ? 'minus' : 'search-check' }}"></i></span>
                            <div>
                                <strong>{{ $item['title'] }}</strong>
                                <p>{{ $item['detail'] }}</p>
                                <span class="decision-proof"><i data-lucide="{{ $item['kind'] === 'editorial' ? 'notebook-tabs' : 'info' }}"></i>{{ $item['kind'] === 'editorial' ? 'Editorial review' : 'Profile transparency check' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        @endif
    </div>

    @if($briefSignals->isNotEmpty())
        <div class="decision-signal-strip" aria-label="Decision evidence summary">
            @foreach($briefSignals as $signal)
                <div><i data-lucide="{{ $signal['icon'] }}"></i><span>{{ $signal['label'] }}</span><strong>{{ $signal['value'] }}</strong></div>
            @endforeach
        </div>
    @endif

    @if($briefAlternatives->isNotEmpty())
        <div class="decision-compare-row">
            <div><span><i data-lucide="scale"></i>Compare before deciding</span><p>Alternatives are ranked from structured use-case, capability, pricing, platform and taxonomy overlap.</p></div>
            <div>
                @foreach($briefAlternatives as $alternative)
                    <a href="{{ route('tools.show', $alternative['slug']) }}">
                        <b>{{ $alternative['name'] }}</b>
                        <span>
                            {{ number_format((float) $alternative['score'], 0) }}% match
                            @if(!empty($alternative['reasons']))
                                · {{ implode(' · ', $alternative['reasons']) }}
                            @endif
                        </span>
                        <i data-lucide="arrow-up-right"></i>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <p class="decision-method-note"><i data-lucide="shield-check"></i>{{ $editorialBrief['method_note'] }}</p>
</section>
