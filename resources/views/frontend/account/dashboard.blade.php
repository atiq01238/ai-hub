@extends('frontend.layouts.app')
@section('title','My AI Hub — Personal Dashboard')
@section('meta_description','Your private AI Hub dashboard for saved items, reviews, follows, comparisons and Test Lab history.')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/account.css') }}">@endpush

@section('content')
<section class="account-page">
    <div class="account-shell">
        @include('frontend.account._sidebar')

        <div class="account-main">
            <header class="account-welcome">
                <div>
                    <span class="account-kicker"><i data-lucide="sparkles"></i> YOUR AI WORKSPACE</span>
                    <h1>Welcome back, <em>{{ $user->name }}</em>.</h1>
                    <p>Your saved research, reviews, comparisons and recent AI activity in one place.</p>
                </div>
                <div class="account-header-actions">
                    <a href="{{ route('comparisons.builder') }}"><i data-lucide="scale"></i>Compare AI</a>
                    <a href="{{ route('account.settings') }}"><i data-lucide="sliders-horizontal"></i>Customize</a>
                </div>
            </header>

            @if(session('status'))
                <div class="account-success"><i data-lucide="circle-check"></i>{{ session('status') }}</div>
            @endif


            @unless($onboardingComplete)
            <section class="account-personalize-banner">
                <div><span><i data-lucide="wand-sparkles"></i> PERSONALIZE AI HUB</span><h2>Make discovery work for you.</h2><p>Choose your AI interests and use cases. It takes about 20 seconds.</p></div>
                <a href="{{ route('account.onboarding') }}">Personalize now <i data-lucide="arrow-right"></i></a>
            </section>
            @endunless

            <section class="account-rec-section">
                <div class="account-section-head"><div><span class="account-kicker"><i data-lucide="sparkles"></i> FOR YOU</span><h2>Recommended AI tools</h2><p>{{ $recommendations['reason'] }}</p></div><a href="{{ route('tools.index') }}">Explore all</a></div>
                <div class="account-rec-grid">
                    @forelse($recommendations['tools'] as $tool)
                    <a class="account-rec-card" href="{{ url('/ai-tools/'.$tool->slug) }}"><span>{{ strtoupper(substr($tool->name,0,2)) }}</span><div><b>{{ $tool->name }}</b><small>{{ $tool->company?->name ?: 'AI Tool' }}</small><p>{{ \Illuminate\Support\Str::limit($tool->short_description ?: $tool->description,90) }}</p></div><i data-lucide="arrow-up-right"></i></a>
                    @empty<div class="account-empty">More recommendations will appear as you use AI Hub.</div>@endforelse
                </div>
            </section>

            <div class="account-stat-grid">
                <a class="account-stat purple" href="{{ route('saved.index') }}"><span><i data-lucide="bookmark"></i></span><div><small>Saved Items</small><strong>{{ number_format($stats['saved']) }}</strong><em>Your research library</em></div></a>
                <a class="account-stat gold" href="{{ route('account.reviews') }}"><span><i data-lucide="star"></i></span><div><small>My Reviews</small><strong>{{ number_format($stats['reviews']) }}</strong><em>Ratings & moderation</em></div></a>
                <a class="account-stat green" href="{{ route('account.following') }}"><span><i data-lucide="users-round"></i></span><div><small>Following</small><strong>{{ number_format($stats['following']) }}</strong><em>Tools, models & companies</em></div></a>
                <a class="account-stat blue" href="{{ route('user.comparisons.index') }}"><span><i data-lucide="scale"></i></span><div><small>Comparisons</small><strong>{{ number_format($stats['comparisons']) }}</strong><em>Saved research</em></div></a>
                <a class="account-stat pink" href="{{ route('user.testlab.history') }}"><span><i data-lucide="flask-conical"></i></span><div><small>Test History</small><strong>{{ number_format($stats['tests']) }}</strong><em>Experiments explored</em></div></a>
            </div>

            <div class="account-two-col">
                <section class="account-panel activity-panel">
                    <div class="account-panel-head"><div><span>ACTIVITY STREAM</span><h2>Recent activity</h2></div><a href="{{ route('account.activity') }}">View all <i data-lucide="arrow-right"></i></a></div>
                    <div class="activity-list">
                        @forelse($recentActivity->take(6) as $item)
                            <a href="{{ $item['url'] }}" class="activity-row">
                                <span class="activity-icon {{ $item['kind'] }}"><i data-lucide="{{ $item['icon'] }}"></i></span>
                                <span class="activity-copy"><strong>{{ $item['title'] }}</strong><small>{{ $item['subtitle'] }}</small></span>
                                <time>{{ $item['at']->diffForHumans() }}</time>
                            </a>
                        @empty
                            <div class="account-empty"><i data-lucide="activity"></i><strong>Your activity will appear here.</strong><span>Save an AI tool, write a review or compare models to get started.</span></div>
                        @endforelse
                    </div>
                </section>

                <section class="account-panel continue-panel">
                    <div class="account-panel-head"><div><span>PICK UP FAST</span><h2>Continue where you left off</h2></div><a href="{{ route('saved.index') }}">Library <i data-lucide="arrow-right"></i></a></div>
                    <div class="continue-grid">
                        @forelse($continueItems as $item)
                            <a class="continue-card" href="{{ $item['url'] }}">
                                <div class="continue-logo">{{ $item['initial'] }}</div>
                                <small>{{ $item['label'] }}</small>
                                <strong>{{ $item['name'] }}</strong>
                                <span>{{ $item['meta'] }}</span>
                                <b>Open <i data-lucide="arrow-up-right"></i></b>
                            </a>
                        @empty
                            <div class="account-empty wide"><i data-lucide="bookmark-plus"></i><strong>No saved research yet.</strong><span>Explore AI Hub and save items you want to revisit.</span></div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="account-bottom-grid">
                <section class="account-panel library-panel">
                    <div class="account-panel-head"><div><span>LIBRARY MIX</span><h2>Your saved intelligence</h2></div><a href="{{ route('saved.index') }}">View library</a></div>
                    @php
                        $breakdown = [
                            'Tools' => (int)($savedBreakdown[\App\Models\Tool::class] ?? 0),
                            'Models' => (int)($savedBreakdown[\App\Models\AiModel::class] ?? 0),
                            'News' => (int)($savedBreakdown[\App\Models\NewsItem::class] ?? 0),
                            'Articles' => (int)($savedBreakdown[\App\Models\Article::class] ?? 0),
                            'Companies' => (int)($savedBreakdown[\App\Models\Company::class] ?? 0),
                        ];
                        $maxBreak = max(1, max($breakdown));
                    @endphp
                    <div class="library-bars">
                        @foreach($breakdown as $label => $value)
                            <div><span><b>{{ $label }}</b><em>{{ $value }}</em></span><i><u style="width:{{ min(100,($value/$maxBreak)*100) }}%"></u></i></div>
                        @endforeach
                    </div>
                </section>

                <section class="account-panel weekly-panel">
                    <div class="account-panel-head"><div><span>LAST 7 DAYS</span><h2>Weekly activity</h2></div><a href="{{ route('account.activity') }}">Full history</a></div>
                    @php($maxWeek = max(1, collect($weeklyActivity)->max('value')))
                    <div class="weekly-chart">
                        @foreach($weeklyActivity as $day)
                            <div class="week-bar"><span><i style="height:{{ max(8,($day['value']/$maxWeek)*100) }}%"></i></span><b>{{ $day['label'] }}</b><em>{{ $day['value'] }}</em></div>
                        @endforeach
                    </div>
                </section>

                <section class="account-panel quick-panel">
                    <div class="account-panel-head"><div><span>QUICK START</span><h2>Jump back into AI Hub</h2></div></div>
                    <div class="quick-links">
                        <a href="{{ route('comparisons.builder') }}"><i data-lucide="scale"></i><span><strong>Compare AI</strong><small>Put tools or models side by side</small></span><b>›</b></a>
                        <a href="{{ route('news.index') }}"><i data-lucide="radio"></i><span><strong>Latest AI News</strong><small>Catch up on launches and research</small></span><b>›</b></a>
                        <a href="{{ route('testlab.index') }}"><i data-lucide="flask-conical"></i><span><strong>Open Test Lab</strong><small>Explore independent experiments</small></span><b>›</b></a>
                        <a href="{{ route('tools.index') }}"><i data-lucide="sparkles"></i><span><strong>Discover Tools</strong><small>Find something useful today</small></span><b>›</b></a>
                    </div>
                </section>
            </div>
        </div>
    </div>
</section>
@endsection
