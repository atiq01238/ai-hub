@extends('frontend.layouts.app')
@section('title','Following — My AI Orbit')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/frontend/account.css') }}">
@endpush

@section('content')
<section class="account-page">
    <div class="account-shell">
        @include('frontend.account._sidebar')

        <div class="account-main">
            <header class="account-subhead">
                <div>
                    <span class="account-kicker">
                        <i data-lucide="bell-plus"></i> FOLLOWING
                    </span>
                    <h1>Your AI watchlist</h1>
                    <p>Tools, models and companies you want to keep close.</p>
                </div>

                <a href="{{ route('trending.index') }}">
                    <i data-lucide="flame"></i>
                    Discover trending
                </a>
            </header>

            <div class="following-grid">
                @forelse($following as $row)
                    @php
                        $target = $row->resolved_target;

                        $url = '#';

                        if ($target) {
                            if ($row->target_type === 'tool') {
                                $url = route('tools.show', $target);
                            } elseif ($row->target_type === 'model') {
                                $url = route('models.show', $target);
                            } elseif ($row->target_type === 'company') {
                                $url = route('companies.show', $target);
                            }
                        }
                    @endphp

                    @if($target)
                        <article class="following-card">
                            <div class="following-card-top">
                                <span class="following-logo">
                                    {{ strtoupper(substr($target->name ?? 'AI', 0, 2)) }}
                                </span>
                                <b>{{ ucfirst($row->target_type) }}</b>
                            </div>

                            <h2>{{ $target->name }}</h2>

                            <p>
                                {{ \Illuminate\Support\Str::limit(
                                    $target->short_description
                                        ?? $target->description
                                        ?? 'Followed on AI Orbit.',
                                    120
                                ) }}
                            </p>

                            <div>
                                <span>
                                    Following since {{ $row->created_at->format('M j, Y') }}
                                </span>

                                <a href="{{ $url }}">
                                    Open
                                    <i data-lucide="arrow-up-right"></i>
                                </a>
                            </div>
                        </article>
                    @endif
                @empty
                    <div class="account-empty big">
                        <i data-lucide="bell-plus"></i>
                        <strong>Your watchlist is empty.</strong>
                        <span>
                            Follow AI tools, models or companies and they will appear here.
                        </span>
                        <a href="{{ route('tools.index') }}">Explore AI Orbit</a>
                    </div>
                @endforelse
            </div>

            <div class="account-pagination">
                {{ $following->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
