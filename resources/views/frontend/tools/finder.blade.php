@extends('frontend.layouts.app')

@section('title', 'AI Tool Finder — Find the Best AI Tools for Your Task | AI Orbit')
@section('meta_description', "Use AI Orbit's free AI Tool Finder to match your task and budget with relevant AI tools using structured use-case, feature, technical and pricing data.")
@section('canonical', route('tool-finder.index'))
@section('robots', request()->isMethod('post') ? 'noindex,follow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1')


@php
    $finderSeoDescription = "Use AI Orbit's free AI Tool Finder to match your task and budget with relevant AI tools using structured use-case, feature, technical and pricing data.";
    $finderWebPageSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'WebPage',
        'name' => 'AI Tool Finder',
        'description' => $finderSeoDescription,
        'url' => route('tool-finder.index'),
        'isPartOf' => ['@' . 'id' => rtrim(config('brand.url'), '/') . '/#website'],
        'about' => ['@' . 'type' => 'Thing', 'name' => 'AI tools'],
    ];
    $finderBreadcrumbSchema = [
        '@' . 'context' => 'https://schema.org',
        '@' . 'type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@' . 'type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            ['@' . 'type' => 'ListItem', 'position' => 2, 'name' => 'AI Tools', 'item' => route('tools.index')],
            ['@' . 'type' => 'ListItem', 'position' => 3, 'name' => 'AI Tool Finder', 'item' => route('tool-finder.index')],
        ],
    ];
@endphp

@push('head')
<script type="application/ld+json">{!! json_encode($finderWebPageSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($finderBreadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/tool-finder.css') }}?v=20260913-mobile-v1">
@endpush

@php
    $selectedTask = old('task', $criteria['task'] ?? '');
    $selectedShortcut = old('shortcut', $criteria['shortcut'] ?? '');
    $selectedBudget = old('budget', $criteria['budget'] ?? 'any');
    $selectedExperience = old('experience', $criteria['experience'] ?? 'any');
    $selectedPriority = old('priority', $criteria['priority'] ?? 'any');
    $selectedFilters = (array) old('filters', $criteria['filters'] ?? []);

    $budgetLabels = [
        'any' => 'Any',
        'free' => 'Free only',
        '10' => 'Under $10',
        '25' => 'Under $25',
        '50' => 'Under $50',
        '50plus' => '$50+',
    ];
    $experienceLabels = [
        'any' => 'Any level',
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
    ];
    $priorityLabels = [
        'any' => 'No preference',
        'quality' => 'Best quality',
        'value' => 'Best value',
        'ease' => 'Easy to use',
        'privacy' => 'Privacy',
        'api' => 'API available',
    ];
    $advancedFilters = [
        'free_plan' => ['Free plan', 'badge-dollar-sign'],
        'api' => ['API access', 'plug-zap'],
        'open_source' => ['Open source', 'code-2'],
        'mobile' => ['Mobile app', 'smartphone'],
        'browser_extension' => ['Browser extension', 'panels-top-left'],
        'team_collaboration' => ['Team collaboration', 'users'],
        'commercial_use' => ['Commercial use', 'briefcase'],
        'privacy_focused' => ['Privacy focused', 'shield-check'],
    ];
    $intentPreferenceLabels = [
        'free' => 'Free option',
        'api' => 'API access',
        'open_source' => 'Open source / self-hosted',
        'privacy' => 'Privacy',
        'beginner' => 'Beginner friendly',
        'team' => 'Team use',
        'mobile' => 'Mobile access',
    ];
@endphp

@section('content')
<div class="finder-page">
    <section class="finder-hero">
        <div class="finder-hero-glow finder-hero-glow-a"></div>
        <div class="finder-hero-glow finder-hero-glow-b"></div>
        <div class="finder-shell finder-hero-inner">
            <nav class="finder-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><i data-lucide="chevron-right"></i><a href="{{ route('tools.index') }}">AI Tools</a><i data-lucide="chevron-right"></i><span>AI Tool Finder</span></nav>
            <div class="finder-eyebrow"><i data-lucide="sparkles"></i> Free AI Orbit Tool Finder</div>
            <h1>AI Tool Finder: find the best AI tool for <span>your task</span></h1>
            <p>Describe the outcome you want. AI Orbit matches your task, budget and workflow against structured AI tool categories, use cases, features, technical profiles and pricing data.</p>
            <div class="finder-trust-row">
                <span><i data-lucide="mouse-pointer-click"></i> No signup required</span>
                <span><i data-lucide="list-checks"></i> Clear match reasons</span>
                <span><i data-lucide="shield-check"></i> Source-aware product data</span>
            </div>
        </div>
    </section>

    <div class="finder-shell finder-main">
        <form class="finder-form" method="post" action="{{ route('tool-finder.find') }}" data-tool-finder-form>
            @csrf

            <section class="finder-card finder-task-card">
                <div class="finder-step-head">
                    <span class="finder-step-number">1</span>
                    <div>
                        <h2>What do you want AI to help you with?</h2>
                        <p>Describe the job in your own words, choose a shortcut, or do both.</p>
                    </div>
                </div>

                <div class="finder-task-input-wrap">
                    <i data-lucide="message-square-text"></i>
                    <textarea name="task" maxlength="220" rows="3" placeholder="e.g. Laravel coding, video creation, PDF summary...">{{ $selectedTask }}</textarea>
                </div>
                @error('task')<p class="finder-error">{{ $message }}</p>@enderror
                @error('shortcut')<p class="finder-error">{{ $message }}</p>@enderror
                <p class="finder-input-tip"><i data-lucide="lightbulb"></i> Be specific for better matches — for example, “free Laravel coding assistant with API access” or “summarize research PDFs with citations”.</p>

                <div class="finder-shortcut-head"><span>Popular tasks</span><small>Optional shortcut</small></div>
                <div class="finder-shortcuts" role="radiogroup" aria-label="Popular AI tasks">
                    @foreach($shortcuts as $value => $shortcut)
                        <input class="finder-choice-input" type="radio" name="shortcut" id="finder-shortcut-{{ $value }}" value="{{ $value }}" @checked($selectedShortcut === $value)>
                        <label class="finder-shortcut" for="finder-shortcut-{{ $value }}">
                            <i data-lucide="{{ $shortcut['icon'] }}"></i>
                            <span>{{ $shortcut['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="finder-card finder-preferences-card">
                <div class="finder-step-head">
                    <span class="finder-step-number">2</span>
                    <div>
                        <h2>Fine-tune your matches</h2>
                        <p>These preferences are optional. Keep “Any” if you do not want to narrow results.</p>
                    </div>
                </div>

                <div class="finder-preference-grid">
                    <fieldset class="finder-fieldset">
                        <legend><i data-lucide="wallet-cards"></i> Budget</legend>
                        <div class="finder-choice-row">
                            @foreach($budgetLabels as $value => $label)
                                <input class="finder-choice-input" type="radio" name="budget" id="finder-budget-{{ $value }}" value="{{ $value }}" @checked($selectedBudget === $value)>
                                <label for="finder-budget-{{ $value }}">{{ $label }}</label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset class="finder-fieldset">
                        <legend><i data-lucide="graduation-cap"></i> Experience</legend>
                        <div class="finder-choice-row">
                            @foreach($experienceLabels as $value => $label)
                                <input class="finder-choice-input" type="radio" name="experience" id="finder-experience-{{ $value }}" value="{{ $value }}" @checked($selectedExperience === $value)>
                                <label for="finder-experience-{{ $value }}">{{ $label }}</label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset class="finder-fieldset finder-fieldset-priority">
                        <legend><i data-lucide="target"></i> What matters most?</legend>
                        <div class="finder-choice-row">
                            @foreach($priorityLabels as $value => $label)
                                <input class="finder-choice-input" type="radio" name="priority" id="finder-priority-{{ $value }}" value="{{ $value }}" @checked($selectedPriority === $value)>
                                <label for="finder-priority-{{ $value }}">{{ $label }}</label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                <details class="finder-advanced" @if(!empty($selectedFilters)) open @endif>
                    <summary><span><i data-lucide="sliders-horizontal"></i> More filters</span><small>Optional</small></summary>
                    <div class="finder-advanced-grid">
                        @foreach($advancedFilters as $value => [$label, $icon])
                            <label class="finder-check">
                                <input type="checkbox" name="filters[]" value="{{ $value }}" @checked(in_array($value, $selectedFilters, true))>
                                <span class="finder-check-ui"><i data-lucide="check"></i></span>
                                <i data-lucide="{{ $icon }}"></i>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </details>

                @error('filters.*')<p class="finder-error">{{ $message }}</p>@enderror

                <div class="finder-submit-wrap">
                    <button type="submit" class="finder-submit" data-finder-submit>
                        <i data-lucide="sparkles"></i>
                        <span>Find My AI Tools</span>
                        <i data-lucide="arrow-right"></i>
                    </button>
                    <p><i data-lucide="unlock"></i> Results are available without creating an account.</p>
                    <div class="finder-loading" data-finder-loading hidden aria-live="polite">
                        <span>Understanding your task</span><i data-lucide="arrow-right"></i>
                        <span>Matching features</span><i data-lucide="arrow-right"></i>
                        <span>Ranking tools</span>
                    </div>
                </div>
            </section>
        </form>

        @if($finderRan)
            <section class="finder-results" id="finder-results" data-finder-results data-finder-event-id="{{ $finderEventId ?? '' }}" data-finder-click-url="{{ route('tool-finder.click') }}">
                <div class="finder-results-head">
                    <div>
                        <span class="finder-results-kicker"><i data-lucide="wand-sparkles"></i> Personalized shortlist</span>
                        <h2>Your best AI tool matches</h2>
                        <p>Ranked against your selected task and preferences. Match percentages explain algorithmic fit, not a universal product score.</p>
                    </div>
                    <a href="#finder-start" class="finder-edit-link" data-finder-edit><i data-lucide="pencil"></i> Edit preferences</a>
                </div>

                <div class="finder-summary-chips" aria-label="Selected preferences">
                    @if($selectedShortcut && isset($shortcuts[$selectedShortcut]))
                        <span><i data-lucide="sparkles"></i>{{ $shortcuts[$selectedShortcut]['label'] }}</span>
                    @endif
                    @if($selectedTask)
                        <span class="finder-summary-task"><i data-lucide="message-square-text"></i>{{ \Illuminate\Support\Str::limit($selectedTask, 58) }}</span>
                    @endif
                    <span><i data-lucide="wallet-cards"></i>{{ $budgetLabels[$selectedBudget] ?? 'Any' }}</span>
                    <span><i data-lucide="graduation-cap"></i>{{ $experienceLabels[$selectedExperience] ?? 'Any level' }}</span>
                    <span><i data-lucide="target"></i>{{ $priorityLabels[$selectedPriority] ?? 'No preference' }}</span>
                    @foreach(array_slice($intent['use_cases'] ?? [], 0, 2) as $detectedUseCase)
                        <span class="finder-detected-chip"><i data-lucide="scan-search"></i>Detected: {{ $detectedUseCase }}</span>
                    @endforeach
                    @foreach(array_slice($intent['preferences'] ?? [], 0, 2) as $detectedPreference)
                        <span class="finder-detected-chip"><i data-lucide="sparkles"></i>Detected: {{ $intentPreferenceLabels[$detectedPreference] ?? ucfirst(str_replace('_', ' ', $detectedPreference)) }}</span>
                    @endforeach
                </div>

                @if($results->isNotEmpty())
                    <div class="finder-top-results">
                        @foreach($results->take(3) as $index => $result)
                            @php($tool = $result['tool'])
                            <article class="finder-result-card {{ $index === 0 ? 'is-best' : '' }}">
                                <div class="finder-result-accent"></div>
                                <div class="finder-result-topline">
                                    <span class="finder-result-label"><i data-lucide="{{ $result['label_icon'] }}"></i>{{ $result['label'] }}</span>
                                    <span class="finder-match-score">{{ $result['match'] }}% <small>match</small><em>{{ $result['match_band'] }}</em></span>
                                </div>

                                <div class="finder-result-identity">
                                    <img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo" loading="lazy" decoding="async">
                                    <div>
                                        <h3>{{ $tool->name }}</h3>
                                        <p>{{ $tool->subcategoryTerm?->name ?: ($tool->subcategory ?: $tool->category?->name) }}</p>
                                    </div>
                                    @if((float) $tool->rating > 0)
                                        <span class="finder-rating"><i data-lucide="star"></i>{{ number_format((float) $tool->rating, 1) }}/5</span>
                                    @endif
                                </div>

                                <p class="finder-result-description">{{ \Illuminate\Support\Str::limit($tool->short_description ?: $tool->overview, 170) }}</p>

                                <div class="finder-why">
                                    <strong>Why it matches</strong>
                                    <ul>
                                        @forelse($result['reasons'] as $reason)
                                            <li><i data-lucide="circle-check-big"></i>{{ $reason }}</li>
                                        @empty
                                            <li><i data-lucide="circle-check-big"></i>Strong task and taxonomy fit</li>
                                        @endforelse
                                    </ul>
                                </div>

                                <div class="finder-price-row">
                                    <div>
                                        <span>Pricing</span>
                                        <strong>{{ $result['pricing_label'] }}</strong>
                                    </div>
                                    @if($result['pricing_verified_at'])
                                        <span class="finder-verified"><i data-lucide="badge-check"></i> Verified {{ $result['pricing_verified_at']->format('M j, Y') }}</span>
                                    @else
                                        <span class="finder-pricing-note"><i data-lucide="info"></i> Check current plan details</span>
                                    @endif
                                </div>

                                <div class="finder-evidence-row">
                                    <span><i data-lucide="database-zap"></i>{{ $result['evidence_label'] }}</span>
                                    @if($result['verified_sources'] > 0)
                                        <span><i data-lucide="shield-check"></i>{{ $result['verified_sources'] }} verified {{ $result['verified_sources'] === 1 ? 'source' : 'sources' }}</span>
                                    @endif
                                </div>

                                <details class="finder-breakdown">
                                    <summary>How is this match calculated? <i data-lucide="chevron-down"></i></summary>
                                    <div class="finder-breakdown-list">
                                        @foreach($result['breakdown'] as $component)
                                            <div>
                                                <span>{{ $component['label'] }}</span>
                                                <div class="finder-meter"><i style="width: {{ $component['score'] }}%"></i></div>
                                                <strong>{{ $component['score'] }}%</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                </details>

                                <div class="finder-result-actions">
                                    <a class="finder-primary-action" href="{{ route('tools.show', $tool) }}" data-finder-result-link data-finder-action="view" data-tool-id="{{ $tool->id }}">View Tool <i data-lucide="arrow-up-right"></i></a>
                                    <a class="finder-secondary-action" href="{{ route('comparisons.builder', ['type' => 'tool', 'item' => $tool->id]) }}" data-finder-result-link data-finder-action="compare" data-tool-id="{{ $tool->id }}"><i data-lucide="scale"></i> Compare</a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if($results->count() > 3)
                        <details class="finder-more-results">
                            <summary><span>Show {{ $results->count() - 3 }} more matches</span><i data-lucide="chevron-down"></i></summary>
                            <div class="finder-more-grid">
                                @foreach($results->slice(3) as $result)
                                    @php($tool = $result['tool'])
                                    <article class="finder-compact-result">
                                        <div class="finder-compact-head">
                                            <img src="{{ $tool->logo_url }}" alt="{{ $tool->name }} logo" loading="lazy" decoding="async">
                                            <div><h3>{{ $tool->name }}</h3><span>{{ $tool->category?->name }}</span></div>
                                            <strong>{{ $result['match'] }}%</strong>
                                        </div>
                                        <p>{{ \Illuminate\Support\Str::limit($tool->short_description ?: $tool->overview, 120) }}</p>
                                        <div class="finder-compact-meta"><span>{{ $result['pricing_label'] }}</span><span>{{ $result['label'] }}</span></div>
                                        <div class="finder-compact-actions">
                                            <a href="{{ route('tools.show', $tool) }}" data-finder-result-link data-finder-action="view" data-tool-id="{{ $tool->id }}">View Tool</a>
                                            <a href="{{ route('comparisons.builder', ['type' => 'tool', 'item' => $tool->id]) }}" data-finder-result-link data-finder-action="compare" data-tool-id="{{ $tool->id }}">Compare</a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </details>
                    @endif
                @else
                    <div class="finder-no-results">
                        <div><i data-lucide="search-x"></i></div>
                        <h3>No confident matches with all of those requirements</h3>
                        <p>No result cleared every requirement with enough task relevance. Try one of these adjustments:</p>
                        @if(!empty($adjustmentTips))
                            <ul class="finder-adjustment-tips">
                                @foreach($adjustmentTips as $tip)<li><i data-lucide="circle-check"></i>{{ $tip }}</li>@endforeach
                            </ul>
                        @endif
                        <a href="#finder-start" data-finder-edit><i data-lucide="sliders-horizontal"></i> Adjust preferences</a>
                    </div>
                @endif
            </section>
        @endif

        <section class="finder-method-note">
            <div><i data-lucide="shield-check"></i></div>
            <div>
                <h2>How AI Orbit Finder works</h2>
                <p>The Finder uses deterministic matching against the AI Orbit catalog: category, subcategory, use cases, features, tags, platform details, technical profile and pricing. Verified taxonomy and source coverage also help break close ranking ties. It does not ask an external AI model to invent product facts or rankings.</p>
            </div>
        </section>

        <section class="finder-seo-guide" aria-labelledby="finder-guide-title">
            <div class="finder-seo-intro">
                <span><i data-lucide="compass"></i> AI tool discovery guide</span>
                <h2 id="finder-guide-title">Find AI tools by task, budget and workflow</h2>
                <p>AI tools are easier to choose when you start with the job you need to complete instead of a long product list. Use the Finder for a personalized shortlist, or browse AI Orbit's canonical directories when you want to explore the market more broadly.</p>
            </div>

            <div class="finder-seo-grid">
                <article>
                    <i data-lucide="message-square-text"></i>
                    <h3>1. Describe the outcome</h3>
                    <p>Enter a real task such as coding, writing, image generation, video creation, research, voice work, marketing or office productivity.</p>
                </article>
                <article>
                    <i data-lucide="sliders-horizontal"></i>
                    <h3>2. Add useful constraints</h3>
                    <p>Set a budget, experience level or priority only when it matters. Advanced filters can require API access, open source, mobile support, collaboration or privacy controls.</p>
                </article>
                <article>
                    <i data-lucide="list-checks"></i>
                    <h3>3. Review why each tool matches</h3>
                    <p>Results show task fit, pricing context, match reasons and evidence signals so you can understand the recommendation before opening or comparing a tool.</p>
                </article>
            </div>

            <div class="finder-seo-links">
                <div>
                    <h3>Explore AI tools another way</h3>
                    <p>Use crawlable, canonical AI Orbit pages when you want to browse rather than answer the Finder questions.</p>
                </div>
                <div class="finder-seo-link-list">
                    <a href="{{ route('tools.index') }}">Browse all AI tools <i data-lucide="arrow-right"></i></a>
                    <a href="{{ route('categories.index') }}">Explore AI tool categories <i data-lucide="arrow-right"></i></a>
                    <a href="{{ route('features.index') }}">Browse AI features <i data-lucide="arrow-right"></i></a>
                    <a href="{{ route('use-cases.index') }}">Browse AI use cases <i data-lucide="arrow-right"></i></a>
                    <a href="{{ route('pricing.index') }}">Check AI pricing intelligence <i data-lucide="arrow-right"></i></a>
                </div>
            </div>

            <div class="finder-faq">
                <h2>AI Tool Finder questions</h2>
                <details><summary>What is an AI Tool Finder?</summary><p>An AI Tool Finder narrows a large catalog into tools relevant to a specific job. AI Orbit uses structured product data and your selected constraints to rank suitable matches.</p></details>
                <details><summary>Do I need an account to use it?</summary><p>No. You can run the Finder and open results without signing up. Account-only actions can remain separate from the recommendation flow.</p></details>
                <details><summary>Does a higher match percentage mean the tool is universally better?</summary><p>No. The percentage describes fit for the task and preferences you entered. It is not a universal quality score and can change when your requirements change.</p></details>
                <details><summary>How does AI Orbit avoid inventing recommendations?</summary><p>The ranking uses existing catalog fields such as categories, use cases, features, tags, technical profiles and pricing. Source and verification signals can improve ordering, but the Finder does not create unsupported product facts.</p></details>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/frontend/tool-finder.js') }}?v=20260913-v3"></script>
@endpush
