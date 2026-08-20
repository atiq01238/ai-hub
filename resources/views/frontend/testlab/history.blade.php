@extends('frontend.layouts.app')
@section('title','My Test Lab History | AI Hub')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/testlab.css') }}">@endpush
@section('content')
<section class="lab-hero"><div class="lab-wrap"><div class="lab-hero-grid"><div>
    <span class="lab-eyebrow"><i data-lucide="history"></i> YOUR LAB HISTORY</span>
    <h1>Tests you have explored.</h1>
    <p>A personal record of Test Lab experiments viewed while signed in.</p>
</div></div></div></section>
<section class="lab-directory"><div class="lab-wrap">
    <div class="lab-results">
        @forelse($history as $entry)
            @php($test=$tests->get($entry->target_id))
            @if($test)
                <article class="lab-test-card">
                    <div class="lab-card-top"><div><span class="lab-category">{{ $test->category ?: 'General' }}</span><span class="lab-id">TEST #{{ str_pad($test->id,3,'0',STR_PAD_LEFT) }}</span></div><span class="lab-model-count"><i data-lucide="layers-3"></i>{{ $test->results_count }} models</span></div>
                    <h3><a href="{{ route('testlab.show',$test) }}">{{ $test->name }}</a></h3>
                    <p class="lab-prompt"><i data-lucide="terminal-square"></i>{{ \Illuminate\Support\Str::limit($test->prompt,125) }}</p>
                    <div class="lab-card-foot"><span><i data-lucide="clock-3"></i>Viewed {{ $entry->updated_at->diffForHumans() }}</span><a href="{{ route('testlab.show',$test) }}">Open experiment <i data-lucide="arrow-right"></i></a></div>
                </article>
            @endif
        @empty
            <div class="lab-empty"><i data-lucide="history"></i><h3>No Test Lab history yet</h3><p>Experiments you view while signed in will appear here.</p></div>
        @endforelse
    </div>
    <div style="margin-top:20px">{{ $history->links() }}</div>
</div></section>
@endsection
