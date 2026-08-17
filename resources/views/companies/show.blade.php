@extends('layouts.admin')

@section('title', $company->name . ' · Company')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/companies.css') }}">
@endpush

@section('content')
@php
    $statusType = $company->status === 'active' ? 'pos' : ($company->status === 'acquired' ? 'warn' : 'neutral');
    $initials = collect(preg_split('/\s+/', trim($company->name)))
        ->filter()
        ->take(2)
        ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<div class="companies-page company-profile">
    <x-page-header
        :title="$company->name"
        subtitle="Company intelligence profile and connected AI portfolio."
        :breadcrumb="['AI Management', 'AI Companies', $company->name]"
    >
        <x-slot:actions>
            <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left"></i>
                All Companies
            </a>
            @if(auth()->user()->canAccessModule('AI Companies', 'Edit'))
                <a href="{{ route('admin.companies.edit', $company->id) }}" class="btn btn-primary">
                    <i data-lucide="pencil"></i>
                    Edit Company
                </a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))
        <div class="alert alert-success companies-flash">
            <i data-lucide="check-circle-2"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <section class="company-profile__hero card">
        <div class="company-profile__identity">
            <div class="company-profile__logo">
                @if($company->logo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($company->logo_path) }}" alt="{{ $company->name }} logo">
                @else
                    <span>{{ $initials ?: 'AI' }}</span>
                @endif
            </div>

            <div class="company-profile__headline">
                <div class="company-profile__meta">
                    <x-status-badge status="{{ ucfirst($company->status) }}" type="{{ $statusType }}" />
                    <span class="company-profile__slug">{{ $company->slug }}</span>
                </div>
                <h1>{{ $company->name }}</h1>
                <p>{{ $company->description ?: 'No company overview has been added yet.' }}</p>

                <div class="company-profile__links">
                    @if($company->website)
                        <a class="btn btn-secondary" href="{{ $company->website }}" target="_blank" rel="noopener noreferrer">
                            <i data-lucide="globe-2"></i>
                            Visit website
                            <i data-lucide="external-link"></i>
                        </a>
                    @endif
                    <a class="company-profile__anchor" href="#tools">
                        <i data-lucide="wrench"></i>
                        {{ number_format($company->tools_count) }} tools
                    </a>
                    <a class="company-profile__anchor" href="#models">
                        <i data-lucide="brain"></i>
                        {{ number_format($company->models_count) }} models
                    </a>
                </div>
            </div>
        </div>

        <div class="company-profile__signal">
            <span class="companies-eyebrow">Registry signal</span>
            <strong>{{ number_format($company->tools_count + $company->models_count) }}</strong>
            <small>Connected AI assets</small>
        </div>
    </section>

    <section class="company-profile__metrics">
        <article class="card company-profile__metric">
            <span><i data-lucide="wrench"></i></span>
            <div><strong>{{ number_format($company->tools_count) }}</strong><small>AI Tools</small></div>
        </article>
        <article class="card company-profile__metric">
            <span><i data-lucide="brain-circuit"></i></span>
            <div><strong>{{ number_format($company->models_count) }}</strong><small>AI Models</small></div>
        </article>
        <article class="card company-profile__metric">
            <span><i data-lucide="calendar-days"></i></span>
            <div><strong>{{ $company->founded_year ?: '—' }}</strong><small>Founded</small></div>
        </article>
        <article class="card company-profile__metric">
            <span><i data-lucide="activity"></i></span>
            <div><strong>{{ ucfirst($company->status) }}</strong><small>Operating status</small></div>
        </article>
    </section>

    <div class="company-profile__layout">
        <main class="company-profile__main">
            <section id="overview" class="card company-profile__section">
                <div class="company-profile__section-head">
                    <div>
                        <span class="companies-eyebrow">Overview</span>
                        <h2>Company intelligence</h2>
                    </div>
                    <i data-lucide="building-2"></i>
                </div>

                <div class="company-profile__overview">
                    <p>{{ $company->description ?: 'No company description yet.' }}</p>
                </div>
            </section>

            <section id="tools" class="card company-profile__section">
                <div class="company-profile__section-head">
                    <div>
                        <span class="companies-eyebrow">Portfolio</span>
                        <h2>AI Tools</h2>
                        <p>Latest connected products from this company.</p>
                    </div>
                    <span class="company-profile__count">{{ number_format($company->tools_count) }}</span>
                </div>

                @if($company->tools->count())
                    <div class="company-assets">
                        @foreach($company->tools as $tool)
                            <a class="company-asset" href="{{ route('admin.tools.show', $tool->id) }}">
                                <span class="company-asset__icon"><i data-lucide="wrench"></i></span>
                                <div class="company-asset__copy">
                                    <strong>{{ $tool->name }}</strong>
                                    <span>
                                        {{ ucfirst($tool->status) }}
                                        @if(!is_null($tool->rating))
                                            · {{ number_format((float) $tool->rating, 1) }} rating
                                        @endif
                                    </span>
                                </div>
                                <i class="company-asset__arrow" data-lucide="arrow-up-right"></i>
                            </a>
                        @endforeach
                    </div>

                    @if($company->tools_count > $company->tools->count())
                        <p class="company-profile__limited-note">
                            Showing the latest {{ $company->tools->count() }} of {{ $company->tools_count }} linked tools.
                        </p>
                    @endif
                @else
                    <div class="company-profile__empty">
                        <i data-lucide="wrench"></i>
                        <strong>No linked tools</strong>
                        <span>This company does not currently have AI tools attached to its profile.</span>
                    </div>
                @endif
            </section>

            <section id="models" class="card company-profile__section">
                <div class="company-profile__section-head">
                    <div>
                        <span class="companies-eyebrow">Model portfolio</span>
                        <h2>AI Models</h2>
                        <p>Latest model records associated with this organization.</p>
                    </div>
                    <span class="company-profile__count">{{ number_format($company->models_count) }}</span>
                </div>

                @if($company->models->count())
                    <div class="company-assets">
                        @foreach($company->models as $model)
                            <a class="company-asset" href="{{ route('admin.models.show', $model->id) }}">
                                <span class="company-asset__icon company-asset__icon--model"><i data-lucide="brain-circuit"></i></span>
                                <div class="company-asset__copy">
                                    <strong>{{ $model->name }}</strong>
                                    <span>
                                        {{ $model->version ?: 'Version not set' }}
                                        · {{ ucfirst($model->status) }}
                                    </span>
                                </div>
                                <i class="company-asset__arrow" data-lucide="arrow-up-right"></i>
                            </a>
                        @endforeach
                    </div>

                    @if($company->models_count > $company->models->count())
                        <p class="company-profile__limited-note">
                            Showing the latest {{ $company->models->count() }} of {{ $company->models_count }} linked models.
                        </p>
                    @endif
                @else
                    <div class="company-profile__empty">
                        <i data-lucide="brain-circuit"></i>
                        <strong>No linked models</strong>
                        <span>This company does not currently have AI models attached to its profile.</span>
                    </div>
                @endif
            </section>
        </main>

        <aside class="company-profile__aside">
            <section class="card company-profile__facts">
                <span class="companies-eyebrow">Company facts</span>
                <dl>
                    <div>
                        <dt>Status</dt>
                        <dd>{{ ucfirst($company->status) }}</dd>
                    </div>
                    <div>
                        <dt>Founded</dt>
                        <dd>{{ $company->founded_year ?: 'Not provided' }}</dd>
                    </div>
                    <div>
                        <dt>Tools</dt>
                        <dd>{{ number_format($company->tools_count) }}</dd>
                    </div>
                    <div>
                        <dt>Models</dt>
                        <dd>{{ number_format($company->models_count) }}</dd>
                    </div>
                    <div>
                        <dt>Slug</dt>
                        <dd class="mono">{{ $company->slug }}</dd>
                    </div>
                </dl>
            </section>

            @if($company->website)
                <section class="card company-profile__web">
                    <span class="companies-eyebrow">Web presence</span>
                    <div class="company-profile__web-icon"><i data-lucide="globe-2"></i></div>
                    <strong>{{ preg_replace('#^https?://(www\.)?#', '', rtrim($company->website, '/')) }}</strong>
                    <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer">
                        Open official website
                        <i data-lucide="external-link"></i>
                    </a>
                </section>
            @endif

            @if(auth()->user()->canAccessModule('AI Companies', 'Delete'))
                <section class="card company-profile__danger">
                    <span class="companies-eyebrow">Record controls</span>
                    <h3>Delete company</h3>
                    <p>Tools and models will be kept and safely detached from this company.</p>
                    <form
                        method="POST"
                        action="{{ route('admin.companies.destroy', $company->id) }}"
                        onsubmit="return confirm('Delete {{ addslashes($company->name) }}? Related tools/models will be kept but detached.')"
                    >
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">
                            <i data-lucide="trash-2"></i>
                            Delete company
                        </button>
                    </form>
                </section>
            @endif
        </aside>
    </div>
</div>
@endsection