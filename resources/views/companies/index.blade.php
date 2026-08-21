@extends('layouts.admin')

@section('title', 'AI Companies')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/companies.css') }}">
@endpush

@section('content')
@php
    $activeCount = $companies->getCollection()->where('status', 'active')->count();
    $toolCount = $companies->getCollection()->sum('tools_count');
    $modelCount = $companies->getCollection()->sum('models_count');
@endphp

<div class="companies-page">
    <x-page-header
        title="AI Companies"
        subtitle="Manage organizations powering the AI ecosystem and their connected products."
        :breadcrumb="['AI Management', 'AI Companies']"
    >
        <x-slot:actions>
            @if(auth()->user()->canAccessModule('AI Companies', 'Add'))
                <a href="{{ route('admin.data-import.index') }}" class="btn btn-secondary">
                    <i data-lucide="file-up"></i>
                    Bulk Import
                </a>
                <a href="{{ route('admin.companies.create') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    Add Company
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

    <section class="companies-overview" aria-label="Company directory snapshot">
        <article class="companies-stat">
            <span class="companies-stat__icon"><i data-lucide="building-2"></i></span>
            <div>
                <span class="companies-stat__label">Directory</span>
                <strong class="companies-stat__value">{{ number_format($companies->total()) }}</strong>
                <small>Total company records</small>
            </div>
        </article>

        <article class="companies-stat">
            <span class="companies-stat__icon companies-stat__icon--success"><i data-lucide="activity"></i></span>
            <div>
                <span class="companies-stat__label">Active on page</span>
                <strong class="companies-stat__value">{{ number_format($activeCount) }}</strong>
                <small>Currently operating</small>
            </div>
        </article>

        <article class="companies-stat">
            <span class="companies-stat__icon companies-stat__icon--violet"><i data-lucide="wrench"></i></span>
            <div>
                <span class="companies-stat__label">Tools on page</span>
                <strong class="companies-stat__value">{{ number_format($toolCount) }}</strong>
                <small>Connected AI products</small>
            </div>
        </article>

        <article class="companies-stat">
            <span class="companies-stat__icon companies-stat__icon--cyan"><i data-lucide="brain-circuit"></i></span>
            <div>
                <span class="companies-stat__label">Models on page</span>
                <strong class="companies-stat__value">{{ number_format($modelCount) }}</strong>
                <small>Connected AI models</small>
            </div>
        </article>
    </section>

    <form method="GET" action="{{ route('admin.companies.index') }}" class="companies-filter card">
        <div class="companies-filter__search">
            <i data-lucide="search"></i>
            <input
                class="input"
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search company, website or description..."
                aria-label="Search companies"
            >
        </div>

        <select class="select" name="status" aria-label="Filter by status">
            <option value="">All statuses</option>
            @foreach(['active' => 'Active', 'acquired' => 'Acquired', 'inactive' => 'Inactive'] as $value => $label)
                <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
            @endforeach
        </select>

        <button class="btn btn-secondary" type="submit">
            <i data-lucide="sliders-horizontal"></i>
            Apply filters
        </button>

        @if(request()->filled('search') || request()->filled('status'))
            <a class="btn btn-ghost" href="{{ route('admin.companies.index') }}">
                <i data-lucide="rotate-ccw"></i>
                Reset
            </a>
        @endif
    </form>

    <section class="companies-card card">
        <div class="companies-card__head">
            <div>
                <span class="companies-eyebrow">Company Registry</span>
                <h2>Organization intelligence</h2>
                <p>Ownership view across AI tools and model portfolios.</p>
            </div>
            <span class="companies-result-count">{{ number_format($companies->total()) }} records</span>
        </div>

        @if($companies->count())
            <div class="table-wrap">
                <table class="data-table companies-table">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Portfolio</th>
                            <th>Founded</th>
                            <th>Website</th>
                            <th class="companies-table__actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companies as $company)
                            @php
                                $initials = collect(preg_split('/\s+/', trim($company->name)))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                                    ->implode('');
                                $statusType = $company->status === 'active' ? 'pos' : ($company->status === 'acquired' ? 'warn' : 'neutral');
                            @endphp
                            <tr>
                                <td>
                                    <div class="company-identity">
                                        <a class="company-avatar" href="{{ route('admin.companies.show', $company->id) }}" aria-label="Open {{ $company->name }}">
                                            @if($company->logo_path)
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($company->logo_path) }}" alt="{{ $company->name }} logo">
                                            @else
                                                <span>{{ $initials ?: 'AI' }}</span>
                                            @endif
                                        </a>
                                        <div class="company-identity__copy">
                                            <a class="company-identity__name" href="{{ route('admin.companies.show', $company->id) }}">
                                                {{ $company->name }}
                                            </a>
                                            <span class="company-identity__slug">{{ $company->slug }}</span>
                                            @if($company->description)
                                                <span class="company-identity__description">{{ \Illuminate\Support\Str::limit($company->description, 78) }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <x-status-badge
                                        status="{{ ucfirst($company->status) }}"
                                        type="{{ $statusType }}"
                                    />
                                </td>
                                <td>
                                    <div class="company-portfolio">
                                        <span><i data-lucide="wrench"></i><b>{{ number_format($company->tools_count) }}</b> Tools</span>
                                        <span><i data-lucide="brain"></i><b>{{ number_format($company->models_count) }}</b> Models</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="company-year">{{ $company->founded_year ?: '—' }}</span>
                                </td>
                                <td>
                                    @if($company->website)
                                        <a class="company-site" href="{{ $company->website }}" target="_blank" rel="noopener noreferrer">
                                            <span>{{ preg_replace('#^https?://(www\.)?#', '', rtrim($company->website, '/')) }}</span>
                                            <i data-lucide="external-link"></i>
                                        </a>
                                    @else
                                        <span class="company-muted">Not provided</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="company-actions">
                                        <a class="icon-btn" href="{{ route('admin.companies.show', $company->id) }}" title="View company">
                                            <i data-lucide="eye"></i>
                                        </a>

                                        @if(auth()->user()->canAccessModule('AI Companies', 'Edit'))
                                            <a class="icon-btn" href="{{ route('admin.companies.edit', $company->id) }}" title="Edit company">
                                                <i data-lucide="pencil"></i>
                                            </a>
                                        @endif

                                        @if(auth()->user()->canAccessModule('AI Companies', 'Delete'))
                                            <form
                                                method="POST"
                                                action="{{ route('admin.companies.destroy', $company->id) }}"
                                                onsubmit="return confirm('Delete {{ addslashes($company->name) }}? Related tools/models will be kept but detached.')"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button class="icon-btn icon-btn--danger" type="submit" title="Delete company">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="companies-pager">
                <span>
                    Showing {{ $companies->firstItem() ?? 0 }}–{{ $companies->lastItem() ?? 0 }}
                    of {{ $companies->total() }}
                </span>
                <div>{{ $companies->links() }}</div>
            </div>
        @else
            <div class="companies-empty">
                <span class="companies-empty__icon"><i data-lucide="building-2"></i></span>
                <h3>{{ request()->hasAny(['search', 'status']) ? 'No matching companies' : 'No companies yet' }}</h3>
                <p>
                    {{ request()->hasAny(['search', 'status'])
                        ? 'Try changing or clearing the current filters.'
                        : 'Create the first company record to start building your AI organization directory.' }}
                </p>
                <div class="companies-empty__actions">
                    @if(request()->hasAny(['search', 'status']))
                        <a class="btn btn-secondary" href="{{ route('admin.companies.index') }}">Clear filters</a>
                    @elseif(auth()->user()->canAccessModule('AI Companies', 'Add'))
                        <a class="btn btn-primary" href="{{ route('admin.companies.create') }}">
                            <i data-lucide="plus"></i>
                            Add Company
                        </a>
                    @endif
                </div>
            </div>
        @endif
    </section>
</div>
@endsection