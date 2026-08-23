@extends('frontend.layouts.app')
@section('title','My Test Lab History | AI Hub')
@section('meta_description','Your recently viewed AI Test Lab experiments.')
@push('head')<meta name="robots" content="noindex,nofollow">@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/testlab.css') }}">@endpush
@section('content')
<section class="lab-hero"><div class="lab-wrap"><div class="lab-hero-grid"><div class="lab-hero-copy"><span class="lab-eyebrow"><i data-lucide="history"></i> YOUR LAB HISTORY</span><h1>Tests you have <span>explored.</span></h1><p>A personal record of public Test Lab experiments viewed while signed in.</p><div class="lab-hero-links"><a href="{{ route('testlab.index') }}"><i data-lucide="flask-conical"></i>Browse Test Lab</a><a href="{{ route('testlab.leaderboard') }}"><i data-lucide="trophy"></i>Model leaderboard</a></div></div></div></div></section>
<section class="lab-directory"><div class="lab-wrap"><div class="lab-results">
    @forelse($history as $entry)
        @php($test=$tests->get($entry->target_id))
        @if($test)
            <article class="lab-test-card">
                <div class="lab-card-top"><div class="lab-card-badges"><span class="lab-category">{{ $test->category ?: 'General' }}</span>@if($test->is_verified)<span class="verified"><i data-lucide="badge-check"></i>Verified</span>@endif</div><span class="lab-model-count"><i data-lucide="layers-3"></i>{{ $test->results_count }} models</span></div>
                <h3><a href="{{ route('testlab.show',$test) }}">{{ $test->name }}</a></h3>
                <p class="lab-summary">{{ $test->short_description ?: \Illuminate\Support\Str::limit($test->prompt,150) }}</p>
                <div class="lab-prompt"><i data-lucide="terminal-square"></i><span>{{ \Illuminate\Support\Str::limit($test->prompt,125) }}</span></div>
                <div class="lab-card-foot"><span><i data-lucide="clock-3"></i>Viewed {{ $entry->updated_at->diffForHumans() }}</span><a href="{{ route('testlab.show',$test) }}">Open experiment <i data-lucide="arrow-right"></i></a></div>
            </article>
        @endif
    @empty
        <div class="lab-empty"><i data-lucide="history"></i><h3>No Test Lab history yet</h3><p>Published experiments you view while signed in will appear here.</p></div>
    @endforelse
</div>@if($history->hasPages())<div class="lab-pagination">{{ $history->links() }}</div>@endif</div></section>
@endsection
