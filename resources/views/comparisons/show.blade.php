@extends('layouts.admin')
@section('title', $comparison->title . ' · Comparison')

@section('content')

<x-page-header title="{{ $comparison->title }}" subtitle="{{ number_format($comparison->views) }} views · {{ ucfirst($comparison->status) }}" :breadcrumb="['Comparison & Benchmarks', 'Comparisons', $comparison->title]">
    <x-slot:actions>
        <a href="{{ route('admin.comparisons.edit', $comparison->id) }}" class="btn btn-primary btn-sm"><i data-lucide="pencil"></i> Edit</a>
        <form action="{{ route('admin.comparisons.destroy', $comparison->id) }}" method="POST" onsubmit="return confirm('Delete this comparison?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="trash-2"></i> Delete</button>
        </form>
    </x-slot:actions>
</x-page-header>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Metric</th>
                @foreach ($items as $item)
                <th><div class="row-media"><div class="thumb">{{ substr($item->name, 0, 2) }}</div>{{ $item->name }}</div></th>
                @endforeach
            </tr>
        </thead>
        <tbody>
        @if ($comparison->comparable_type === 'tool')
            <tr>
                <td class="text-sub">Company</td>
                @foreach ($items as $item)<td>{{ $item->company->name ?? '—' }}</td>@endforeach
            </tr>
            <tr>
                <td class="text-sub">Rating</td>
                @foreach ($items as $item)<td class="mono">{{ number_format($item->rating, 1) }} ★</td>@endforeach
            </tr>
            <tr>
                <td class="text-sub">Popularity</td>
                @foreach ($items as $item)<td><div class="progress" style="width:100px;"><span style="width:{{ $item->popularity }}%;"></span></div></td>@endforeach
            </tr>
            <tr>
                <td class="text-sub">Pricing</td>
                @foreach ($items as $item)<td class="text-sub">{{ implode(', ', $item->pricing_models ?? []) ?: '—' }}</td>@endforeach
            </tr>
            <tr>
                <td class="text-sub">Capabilities</td>
                @foreach ($items as $item)
                <td>
                    <div class="flex gap-8" style="flex-wrap:wrap;">
                        @foreach ($item->capabilities ?? [] as $c)<span class="badge badge-neutral">{{ $c }}</span>@endforeach
                    </div>
                </td>
                @endforeach
            </tr>
        @else
            <tr>
                <td class="text-sub">Company</td>
                @foreach ($items as $item)<td>{{ $item->company->name ?? '—' }}</td>@endforeach
            </tr>
            <tr>
                <td class="text-sub">Benchmark Score</td>
                @foreach ($items as $item)<td class="mono">{{ number_format($item->benchmark_score, 1) }}</td>@endforeach
            </tr>
            <tr>
                <td class="text-sub">Context Window</td>
                @foreach ($items as $item)<td class="mono">{{ $item->context_window ?? '—' }}</td>@endforeach
            </tr>
            <tr>
                <td class="text-sub">Input $/1M</td>
                @foreach ($items as $item)<td class="mono">{{ $item->input_price_per_million !== null ? '$'.number_format($item->input_price_per_million, 2) : '—' }}</td>@endforeach
            </tr>
            <tr>
                <td class="text-sub">Output $/1M</td>
                @foreach ($items as $item)<td class="mono">{{ $item->output_price_per_million !== null ? '$'.number_format($item->output_price_per_million, 2) : '—' }}</td>@endforeach
            </tr>
            <tr>
                <td class="text-sub">Capabilities</td>
                @foreach ($items as $item)
                <td>
                    <div class="flex gap-8" style="flex-wrap:wrap;">
                        @foreach ($item->capabilities ?? [] as $c)<span class="badge badge-violet">{{ $c }}</span>@endforeach
                    </div>
                </td>
                @endforeach
            </tr>
        @endif
        </tbody>
    </table>
    </div>
</div>
@endsection
