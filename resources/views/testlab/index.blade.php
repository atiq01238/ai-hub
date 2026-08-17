@extends('layouts.admin')
@section('title','AI Test Lab')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/final-polish.css') }}">@endpush
@section('content')
<div class="fp-page">
<x-page-header title="AI Test Lab" :subtitle="$tests->total().' structured model test(s)'" :breadcrumb="['Comparison & Benchmarks','AI Test Lab']" />
@if(session('status'))<div class="alert alert-success fp-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger fp-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif
<nav class="fp-tabs"><a href="{{ route('admin.testlab.index') }}" class="{{ !request('category')?'is-active':'' }}"><i data-lucide="flask-conical"></i>All</a>@foreach(['Text','Image','Video','Audio','Coding','Reasoning'] as $cat)<a href="{{ route('admin.testlab.index',['category'=>$cat]) }}" class="{{ request('category')===$cat?'is-active':'' }}">{{ $cat }}</a>@endforeach</nav>
<div class="fp-test-layout">
<aside class="card fp-test-create"><span class="fp-eyebrow">Structured Evaluation</span><h2>Create model test</h2><p>Create 2–4 empty result slots, then paste model outputs and score them on the detail screen.</p><form action="{{ route('admin.testlab.store') }}" method="POST">@csrf
<label class="fp-field"><span>Test Name <b>*</b></span><input class="input" name="name" value="{{ old('name') }}" required placeholder="Long-context summarization"></label>
<label class="fp-field"><span>Prompt <b>*</b></span><textarea class="textarea" name="prompt" rows="5" required>{{ old('prompt') }}</textarea></label>
<label class="fp-field"><span>Category</span><select class="select" name="category">@foreach(['Text','Image','Video','Audio','Coding','Reasoning'] as $cat)<option value="{{ $cat }}" @selected(old('category')===$cat)>{{ $cat }}</option>@endforeach</select></label>
<div class="fp-field"><span>Models to Test · 2–4 <b>*</b></span><div class="fp-model-picker">@foreach($models as $model)<label><input type="checkbox" name="model_ids[]" value="{{ $model->id }}" @checked(in_array($model->id,old('model_ids',[])))><span>{{ $model->name }}</span></label>@endforeach</div></div>
<label class="fp-field"><span>Evaluation Criteria</span><input class="input" name="criteria" value="{{ old('criteria') }}" placeholder="Quality, Accuracy, Speed..."></label>
<label class="fp-field"><span>Expected Output</span><textarea class="textarea" name="expected_output" rows="3">{{ old('expected_output') }}</textarea></label>
<button class="btn btn-primary fp-full"><i data-lucide="plus"></i>Create Test</button>
</form></aside>
<main class="card fp-table-card"><header class="fp-card-head"><div><span class="fp-eyebrow">Test Registry</span><h2>Evaluation sessions</h2><p>Each session contains manually captured model responses and scores.</p></div><span class="fp-count">{{ number_format($tests->total()) }}</span></header>
@if($tests->count())<div class="fp-test-list">@foreach($tests as $test)<article><span class="fp-test-icon"><i data-lucide="flask-conical"></i></span><div class="fp-test-copy"><a href="{{ route('admin.testlab.show',$test->id) }}">{{ $test->name }}</a><small>{{ $test->category??'Uncategorized' }} · {{ $test->results_count }} model result slots · {{ $test->created_at->format('M j, Y') }}</small></div><a href="{{ route('admin.testlab.show',$test->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="chart-no-axes-column"></i>Open</a><form action="{{ route('admin.testlab.destroy',$test->id) }}" method="POST" onsubmit="return confirm('Delete this test and all of its result slots?')">@csrf @method('DELETE')<button class="icon-btn icon-btn--danger"><i data-lucide="trash-2"></i></button></form></article>@endforeach</div><div class="fp-pagination"><span>Showing {{ $tests->firstItem()??0 }}–{{ $tests->lastItem()??0 }} of {{ $tests->total() }}</span><div>{{ $tests->links() }}</div></div>@else<div class="fp-empty"><span><i data-lucide="flask-conical"></i></span><h3>No tests yet</h3><p>Create a structured model evaluation from the left panel.</p></div>@endif
</main></div></div>
@endsection
