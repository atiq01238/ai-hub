@extends('layouts.admin')
@section('title',$test->name)
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/final-polish.css') }}">@endpush
@section('content')
@php($ranked=$test->results->sortByDesc('overall_score')->values())
<div class="fp-page">
<x-page-header :title="$test->name" :subtitle="($test->category??'Uncategorized').' · '.$test->results->count().' model result slots'" :breadcrumb="['Comparison & Benchmarks','AI Test Lab',$test->name]">
<x-slot:actions><a href="{{ route('admin.testlab.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Test Lab</a></x-slot:actions>
</x-page-header>
@if(session('status'))<div class="alert alert-success fp-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger fp-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif
<section class="card fp-test-brief"><div><span class="fp-eyebrow">Evaluation Prompt</span><h1>{{ $test->name }}</h1><p>{{ $test->prompt }}</p>@if($test->criteria)<div class="fp-test-chip"><i data-lucide="list-checks"></i>{{ $test->criteria }}</div>@endif</div>@if($test->expected_output)<aside><span class="fp-eyebrow">Expected Output</span><p>{{ $test->expected_output }}</p></aside>@endif</section>
<section class="fp-result-grid">
@foreach($test->results as $result)
<article class="card fp-result-card"><header><div><span class="fp-result-avatar">{{ mb_strtoupper(mb_substr($result->model->name??'?',0,2)) }}</span><div><strong>{{ $result->model->name??'Unknown model' }}</strong><small>Manual evaluation result</small></div></div><span class="fp-overall">{{ number_format($result->overall_score,1) }}</span></header>
<form action="{{ route('admin.testlab.results.update',$result->id) }}" method="POST">@csrf @method('PUT')
<label class="fp-field"><span>Model Response</span><textarea class="textarea" name="response_text" rows="6">{{ $result->response_text }}</textarea></label>
<div class="fp-score-inputs">@foreach(['score_quality'=>'Quality','score_accuracy'=>'Accuracy','score_prompt_adherence'=>'Prompt Adherence','score_creativity'=>'Creativity','score_speed'=>'Speed'] as $field=>$label)<label><span>{{ $label }}</span><input class="input" type="number" min="0" max="100" name="{{ $field }}" value="{{ $result->{$field} }}"></label>@endforeach</div>
<button class="btn btn-secondary fp-full"><i data-lucide="save"></i>Save Result</button></form></article>
@endforeach
</section>
<section class="card fp-table-card"><header class="fp-card-head"><div><span class="fp-eyebrow">Leaderboard</span><h2>Side-by-side scores</h2><p>Overall score is derived by the model/result layer from recorded criteria.</p></div></header><div class="table-wrap"><table class="data-table fp-table"><thead><tr><th>Rank</th><th>Model</th><th>Quality</th><th>Accuracy</th><th>Prompt Adherence</th><th>Creativity</th><th>Speed</th><th>Overall</th></tr></thead><tbody>@foreach($ranked as $i=>$result)<tr><td><span class="fp-rank">{{ $i+1 }}</span></td><td><strong>{{ $result->model->name??'—' }}</strong></td><td><x-score-meter :value="$result->score_quality??0" :segments="5"/></td><td><x-score-meter :value="$result->score_accuracy??0" :segments="5"/></td><td><x-score-meter :value="$result->score_prompt_adherence??0" :segments="5"/></td><td><x-score-meter :value="$result->score_creativity??0" :segments="5"/></td><td><x-score-meter :value="$result->score_speed??0" :segments="5"/></td><td><strong>{{ number_format($result->overall_score,1) }}</strong></td></tr>@endforeach</tbody></table></div></section>
</div>
@endsection
