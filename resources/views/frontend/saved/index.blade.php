@extends('frontend.layouts.app')
@section('title','Saved AI Library | AI Orbit')
@section('meta_description','Your personal AI Orbit library for saved AI tools, models, news, articles and companies.')

@section('content')
@php
    $typeLabels = ['all'=>'All saved','tool'=>'AI Tools','model'=>'AI Models','news'=>'AI News','article'=>'Articles','company'=>'Companies'];
    $classMap = [
        \App\Models\Tool::class => 'tool',
        \App\Models\AiModel::class => 'model',
        \App\Models\NewsItem::class => 'news',
        \App\Models\Article::class => 'article',
        \App\Models\Company::class => 'company',
    ];
    $countFor = fn($key) => $key === 'all' ? (int)collect($counts)->sum() : (int)($counts[$key === 'tool' ? \App\Models\Tool::class : ($key === 'model' ? \App\Models\AiModel::class : ($key === 'news' ? \App\Models\NewsItem::class : ($key === 'article' ? \App\Models\Article::class : \App\Models\Company::class)))] ?? 0);
@endphp

<section class="saved-hero">
    <div class="saved-wrap">
        <div class="saved-breadcrumb"><a href="{{ route('home') }}">Home</a><i data-lucide="chevron-right"></i><span>Saved Library</span></div>
        <div class="saved-hero-grid">
            <div>
                <span class="saved-kicker"><i data-lucide="bookmark-check"></i> PERSONAL AI LIBRARY</span>
                <h1>Everything worth revisiting,<br><span>saved in one place.</span></h1>
                <p>Keep important tools, models, news, guides and companies together while you research, compare and make AI decisions.</p>
            </div>
            <div class="saved-hero-card">
                <div><i data-lucide="library-big"></i></div>
                @auth
                    <strong>{{ number_format($countFor('all')) }}</strong><span>saved items</span><small>Synced to {{ auth()->user()->email }}</small>
                @else
                    <strong>Sign in</strong><span>to build your library</span><small>Your saved items stay attached to your account.</small>
                @endauth
            </div>
        </div>
    </div>
</section>

<section class="saved-page">
    <div class="saved-wrap">
        @guest
            <div class="saved-guest-panel">
                <div class="guest-icon"><i data-lucide="bookmark-plus"></i></div>
                <div><span class="saved-kicker">YOUR RESEARCH WORKSPACE</span><h2>Sign in to save and organize AI intelligence.</h2><p>Save tools you want to test, models you want to compare, news you want to revisit and articles you want to keep for later.</p><div class="guest-actions"><a class="saved-primary" href="{{ route('login') }}">Sign in <i data-lucide="log-in"></i></a><a class="saved-secondary" href="{{ route('signup') }}">Create account</a></div></div>
                <div class="guest-benefits"><div><i data-lucide="bot"></i><span><strong>Tools & Models</strong><small>Build a research shortlist</small></span></div><div><i data-lucide="newspaper"></i><span><strong>News & Guides</strong><small>Return to important context</small></span></div><div><i data-lucide="cloud"></i><span><strong>Account synced</strong><small>Available whenever you sign in</small></span></div></div>
            </div>
        @else
            <div class="saved-toolbar">
                <div><span class="saved-kicker">YOUR COLLECTION</span><h2>Saved intelligence</h2><p>{{ number_format($countFor('all')) }} items in your personal library.</p></div>
                <a class="saved-discover" href="{{ route('search.index') }}"><i data-lucide="search"></i> Discover more</a>
            </div>

            <nav class="saved-tabs" aria-label="Saved item filters">
                @foreach($typeLabels as $key=>$label)
                    <a class="{{ $type === $key ? 'active' : '' }}" href="{{ route('saved.index',$key==='all'?[]:['type'=>$key]) }}"><span>{{ $label }}</span><b>{{ $countFor($key) }}</b></a>
                @endforeach
            </nav>

            @if($savedItems->count())
                <div class="saved-grid">
                    @foreach($savedItems as $saved)
                        @php
                            $item = $saved->saveable;
                            $itemType = $classMap[$saved->saveable_type] ?? null;
                            if (!$item || !$itemType) continue;
                            $title = $item->name ?? $item->headline ?? $item->title ?? 'Saved item';
                            $subtitle = match($itemType) {
                                'tool' => ($item->company?->name ?? 'Independent').' · '.($item->category?->name ?? 'AI Tool'),
                                'model' => ($item->company?->name ?? 'Independent').' · '.($item->version ?: 'AI Model'),
                                'news' => ($item->source ?: $item->company?->name ?: 'AI News').' · '.optional($item->published_at)->diffForHumans(),
                                'article' => ($item->company?->name ?? 'AI Orbit Editorial').' · '.optional($item->published_at)->format('M j, Y'),
                                'company' => $item->founded_year ? 'Founded '.$item->founded_year : 'AI Company',
                                default => 'AI Orbit',
                            };
                            $description = $item->short_description ?? $item->ai_summary ?? $item->summary ?? $item->description ?? '';
                            $image = match($itemType) {
                                'tool', 'model', 'company' => $item->logo_url,
                                'news' => $item->image_url ?? \App\Support\MediaUrl::placeholder(),
                                'article' => \App\Support\MediaUrl::resolve($item->featured_image_path) ?? \App\Support\MediaUrl::placeholder(),
                                default => \App\Support\MediaUrl::placeholder(),
                            };
                            $url = match($itemType) {
                                'tool' => route('tools.show',$item),
                                'model' => route('models.show',$item),
                                'news' => route('news.show',$item),
                                'article' => route('articles.show',$item),
                                'company' => route('companies.show',$item),
                            };
                        @endphp
                        <article class="saved-card" data-saved-card>
                            <a class="saved-card-image type-{{ $itemType }}" href="{{ $url }}"><img src="{{ $image }}" alt="{{ $title }}" loading="lazy"><span>{{ strtoupper($typeLabels[$itemType]) }}</span></a>
                            <div class="saved-card-body">
                                <div class="saved-card-meta"><span>{{ $subtitle }}</span><time>Saved {{ $saved->created_at->diffForHumans() }}</time></div>
                                <h3><a href="{{ $url }}">{{ $title }}</a></h3>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags((string)$description), 130) }}</p>
                                <div class="saved-card-foot">
                                    <a href="{{ $url }}">Open {{ $itemType }} <i data-lucide="arrow-up-right"></i></a>
                                    <button type="button" class="saved-remove" data-save-item data-save-type="{{ $itemType }}" data-save-id="{{ $item->getKey() }}" data-remove-card="true" aria-label="Remove {{ $title }} from saved"><i data-lucide="bookmark-x"></i> Remove</button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($savedItems->hasPages())
                    <nav class="saved-pagination" aria-label="Saved library pagination">
                        @if($savedItems->onFirstPage())<span class="disabled"><i data-lucide="chevron-left"></i> Previous</span>@else<a href="{{ $savedItems->previousPageUrl() }}"><i data-lucide="chevron-left"></i> Previous</a>@endif
                        <span>Page {{ $savedItems->currentPage() }} of {{ $savedItems->lastPage() }}</span>
                        @if($savedItems->hasMorePages())<a href="{{ $savedItems->nextPageUrl() }}">Next <i data-lucide="chevron-right"></i></a>@else<span class="disabled">Next <i data-lucide="chevron-right"></i></span>@endif
                    </nav>
                @endif
            @else
                <div class="saved-empty">
                    <div><i data-lucide="bookmark"></i></div><span class="saved-kicker">NOTHING HERE YET</span><h2>{{ $type === 'all' ? 'Start building your AI library.' : 'No '.$typeLabels[$type].' saved yet.' }}</h2><p>Use the bookmark action across AI Orbit to keep useful research within reach.</p><a class="saved-primary" href="{{ route('search.index') }}">Explore AI Orbit <i data-lucide="compass"></i></a>
                </div>
            @endif
        @endguest

        <section class="saved-recommendations">
            <div class="saved-section-head"><div><span class="saved-kicker">KEEP DISCOVERING</span><h2>Popular intelligence to explore</h2></div><a href="{{ route('trending.index') }}">View trending <i data-lucide="arrow-right"></i></a></div>
            <div class="saved-rec-grid">
                @foreach($recommendations['tools'] as $tool)
                    <a class="saved-rec-card" href="{{ route('tools.show',$tool) }}"><img src="{{ $tool->logo_url }}" alt=""><div><small>AI TOOL</small><strong>{{ $tool->name }}</strong><span>{{ $tool->company?->name ?? 'Independent' }} · ★ {{ number_format((float)$tool->rating,1) }}</span></div><i data-lucide="arrow-up-right"></i></a>
                @endforeach
                @foreach($recommendations['models'] as $model)
                    <a class="saved-rec-card" href="{{ route('models.show',$model) }}"><img src="{{ $model->logo_url }}" alt=""><div><small>AI MODEL</small><strong>{{ $model->name }}</strong><span>{{ $model->company?->name ?? 'Independent' }} · {{ $model->benchmark_score !== null ? number_format((float)$model->benchmark_score,1).'/100' : 'Benchmark N/A' }}</span></div><i data-lucide="arrow-up-right"></i></a>
                @endforeach
            </div>
        </section>
    </div>
</section>
@endsection
