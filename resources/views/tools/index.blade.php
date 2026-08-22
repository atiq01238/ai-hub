@extends('layouts.admin')
@section('title','AI Tools')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/tools.css') }}">
@endpush

@section('content')
@php
    $hasFilters = request()->filled('search') || request()->filled('category_id') || request()->filled('company_id') || request()->filled('pricing') || request()->filled('status') || request()->filled('rating');
@endphp

<x-page-header title="AI Tools" subtitle="Manage the AI product directory, taxonomy, publishing status and model relationships." :breadcrumb="['AI Management','AI Tools']">
    <x-slot:actions>
        @if(auth()->user()->canAccessModule('AI Tools','Add'))
            <a href="{{ route('admin.tools.create') }}" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add AI Tool</a>
        @endif
    </x-slot:actions>
</x-page-header>

@if(session('status'))
    <div class="alert alert-success tools-alert"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>
@endif

<section class="tools-overview" aria-label="Tool directory overview">
    <div class="tools-overview__copy">
        <span class="tools-eyebrow"><i data-lucide="boxes"></i> Product Directory</span>
        <h2>{{ number_format($tools->total()) }} {{ Str::plural('tool', $tools->total()) }} in this result set</h2>
        <p>Review product ownership, taxonomy, linked models, quality signals and publication state from one workspace.</p>
    </div>
    <div class="tools-overview__meta">
        <div><span>Page</span><strong>{{ $tools->currentPage() }} / {{ max(1, $tools->lastPage()) }}</strong></div>
        <div><span>Per page</span><strong>{{ $tools->perPage() }}</strong></div>
        <div><span>Filters</span><strong>{{ $hasFilters ? 'Active' : 'None' }}</strong></div>
    </div>
</section>

<form method="GET" action="{{ route('admin.tools.index') }}" class="tools-filter card" aria-label="Filter AI tools">
    <div class="tools-filter__search">
        <i data-lucide="search"></i>
        <input class="input" name="search" value="{{ request('search') }}" placeholder="Search tools by name or description..." aria-label="Search tools">
    </div>
    <select class="select" name="category_id" aria-label="Category">
        <option value="">All categories</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}" @selected((string)request('category_id') === (string)$category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <select class="select" name="company_id" aria-label="Company">
        <option value="">All companies</option>
        @foreach($companies as $company)
            <option value="{{ $company->id }}" @selected((string)request('company_id') === (string)$company->id)>{{ $company->name }}</option>
        @endforeach
    </select>
    <select class="select" name="pricing" aria-label="Pricing model">
        <option value="">All pricing</option>
        @foreach(['Free','Freemium','Paid','Enterprise'] as $pricing)
            <option value="{{ $pricing }}" @selected(request('pricing') === $pricing)>{{ $pricing }}</option>
        @endforeach
    </select>
    <select class="select" name="status" aria-label="Status">
        <option value="">All statuses</option>
        @foreach(['draft'=>'Draft','published'=>'Published','archived'=>'Archived'] as $value => $label)
            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <select class="select" name="rating" aria-label="Minimum rating">
        <option value="">Any rating</option>
        <option value="4" @selected(request('rating') === '4')>4.0+ stars</option>
        <option value="3" @selected(request('rating') === '3')>3.0+ stars</option>
    </select>
    <div class="tools-filter__actions">
        <button class="btn btn-secondary btn-sm" type="submit"><i data-lucide="sliders-horizontal"></i> Filter</button>
        @if($hasFilters)
            <a href="{{ route('admin.tools.index') }}" class="btn btn-ghost btn-sm"><i data-lucide="rotate-ccw"></i> Reset</a>
        @endif
    </div>
</form>

<div class="card tools-table-card">
    <div class="tools-table-head">
        <div>
            <span class="tools-eyebrow">Directory Records</span>
            <h3>AI Tool Inventory</h3>
        </div>
        <span class="tools-table-head__count">{{ number_format($tools->total()) }} results</span>
    </div>

    <div class="table-wrap">
        <table class="data-table tools-table">
            <thead>
                <tr>
                    <th>Tool</th>
                    <th>Organization</th>
                    <th>Taxonomy</th>
                    <th>Models</th>
                    <th>Rating</th>
                    <th>Status</th>
                    <th class="tools-actions-col">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($tools as $tool)
                @php
                    $statusType = $tool->status === 'published' ? 'pos' : ($tool->status === 'draft' ? 'warn' : 'neutral');
                    $initials = Str::upper(Str::substr($tool->name, 0, 2));
                @endphp
                <tr>
                    <td class="tools-name-cell">
                        <a href="{{ route('admin.tools.show', $tool->id) }}" class="tool-identity">
                            <span class="tool-identity__logo">
                                @if($tool->logo_path)
                                    <img src="{{ $tool->logo_url }}" alt="">
                                @else
                                    {{ $initials }}
                                @endif
                            </span>
                            <span class="tool-identity__copy">
                                <strong>{{ $tool->name }}</strong>
                                <small>{{ $tool->short_description ?: $tool->slug }}</small>
                            </span>
                        </a>
                    </td>
                    <td>
                        <strong class="tools-cell-primary">{{ $tool->company?->name ?? 'Independent' }}</strong>
                        @if($tool->website)<span class="tools-cell-secondary">Website linked</span>@endif
                    </td>
                    <td>
                        <strong class="tools-cell-primary">{{ $tool->category?->name ?? 'Uncategorized' }}</strong>
                        <span class="tools-cell-secondary">{{ $tool->subcategoryTerm?->name ?? $tool->subcategory ?? 'No subcategory' }}</span>
                    </td>
                    <td><span class="tools-metric"><i data-lucide="brain"></i>{{ $tool->models_count }}</span></td>
                    <td><span class="tools-rating"><i data-lucide="star"></i>{{ number_format((float)$tool->rating, 1) }}</span></td>
                    <td><x-status-badge status="{{ ucfirst($tool->status) }}" type="{{ $statusType }}" /></td>
                    <td>
                        <div class="tools-row-actions">
                            <a class="icon-btn" href="{{ route('admin.tools.show', $tool->id) }}" title="View {{ $tool->name }}"><i data-lucide="eye"></i></a>
                            @if(auth()->user()->canAccessModule('AI Tools','Edit'))
                                <a class="icon-btn" href="{{ route('admin.tools.edit', $tool->id) }}" title="Edit {{ $tool->name }}"><i data-lucide="pencil"></i></a>
                            @endif
                            @if(auth()->user()->canAccessModule('AI Tools','Delete'))
                                <form method="POST" action="{{ route('admin.tools.destroy', $tool->id) }}" onsubmit="return confirm('Delete this tool? Linked models will be kept but detached.')">
                                    @csrf @method('DELETE')
                                    <button class="icon-btn tools-danger-action" type="submit" title="Delete {{ $tool->name }}"><i data-lucide="trash-2"></i></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="tools-empty">
                            <span><i data-lucide="search-x"></i></span>
                            <h3>No AI tools found</h3>
                            <p>{{ $hasFilters ? 'Try adjusting or resetting the current filters.' : 'Create the first tool to start building your AI directory.' }}</p>
                            @if($hasFilters)
                                <a href="{{ route('admin.tools.index') }}" class="btn btn-secondary btn-sm">Clear filters</a>
                            @elseif(auth()->user()->canAccessModule('AI Tools','Add'))
                                <a href="{{ route('admin.tools.create') }}" class="btn btn-primary btn-sm">Add AI Tool</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="tools-pager">
        <span>Showing <strong>{{ $tools->firstItem() ?? 0 }}–{{ $tools->lastItem() ?? 0 }}</strong> of <strong>{{ $tools->total() }}</strong></span>
        <div>{{ $tools->links() }}</div>
    </div>
</div>
@endsection