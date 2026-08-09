@extends('layouts.admin')
@section('title', 'AI Test Lab')

@section('content')

<x-page-header title="AI Test Lab" subtitle="{{ $tests->total() }} tests" :breadcrumb="['Comparison & Benchmarks', 'AI Test Lab']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="filter-bar">
    <a href="{{ route('admin.testlab.index') }}" class="chip {{ !request('category') ? 'is-active' : '' }}">All</a>
    @foreach (['Text','Image','Video','Audio','Coding','Reasoning'] as $cat)
        <a href="{{ route('admin.testlab.index', ['category' => $cat]) }}" class="chip {{ request('category') === $cat ? 'is-active' : '' }}">{{ $cat }}</a>
    @endforeach
</div>

<div class="grid-12">
    <div class="col-4 card card-pad">
        <div class="section-title">Create Test</div>
        <form action="{{ route('admin.testlab.store') }}" method="POST">
            @csrf
            <div class="form-field" style="margin-bottom:12px;"><label>Test Name</label><input class="input" name="name" placeholder="e.g. Long-context summarization" required></div>
            <div class="form-field" style="margin-bottom:12px;"><label>Prompt</label><textarea class="input" name="prompt" rows="4" placeholder="Enter test prompt..." required></textarea></div>
            <div class="form-field" style="margin-bottom:12px;">
                <label>Category</label>
                <select class="select" name="category">
                    @foreach (['Text','Image','Video','Audio','Coding','Reasoning'] as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-field" style="margin-bottom:12px;">
                <label>Models to test (pick 2–4)</label>
                <div style="max-height:160px; overflow-y:auto; display:flex; flex-direction:column; gap:6px;">
                    @foreach ($models as $model)
                        <label class="flex items-center gap-8" style="font-size:13px;">
                            <input type="checkbox" name="model_ids[]" value="{{ $model->id }}">
                            {{ $model->name }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="form-field" style="margin-bottom:12px;"><label>Evaluation Criteria</label><input class="input" name="criteria" placeholder="Quality, Accuracy, Speed..."></div>
            <div class="form-field" style="margin-bottom:16px;"><label>Expected Output</label><textarea class="input" name="expected_output" rows="2" placeholder="Optional reference answer"></textarea></div>
            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;"><i data-lucide="plus"></i> Create Test</button>
        </form>
    </div>

    <div class="col-8">
        <div class="card">
            <div class="card-head"><h3>Tests</h3></div>
            <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Test</th><th>Category</th><th>Models</th><th>Created</th><th></th></tr></thead>
                <tbody>
                @forelse ($tests as $test)
                <tr>
                    <td><a href="{{ route('admin.testlab.show', $test->id) }}"><b>{{ $test->name }}</b></a></td>
                    <td class="text-sub">{{ $test->category ?? '—' }}</td>
                    <td class="mono">{{ $test->results_count }}</td>
                    <td class="cell-sub">{{ $test->created_at->format('M j') }}</td>
                    <td>
                        <div class="flex gap-8">
                            <a href="{{ route('admin.testlab.show', $test->id) }}" class="btn btn-ghost btn-sm">View</a>
                            <form action="{{ route('admin.testlab.destroy', $test->id) }}" method="POST" onsubmit="return confirm('Delete this test and its results?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-sub" style="text-align:center; padding:32px;">No tests yet — create one on the left.</td></tr>
                @endforelse
                </tbody>
            </table>
            </div>
            <div class="pager">{{ $tests->links() }}</div>
        </div>
    </div>
</div>
@endsection
