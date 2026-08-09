@extends('layouts.admin')
@section('title', $test->name)

@section('content')

<x-page-header title="{{ $test->name }}" subtitle="{{ $test->category }}" :breadcrumb="['Comparison & Benchmarks', 'AI Test Lab', $test->name]" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="card card-pad" style="margin-bottom:16px;">
    <div class="section-title">Prompt</div>
    <p class="text-sub" style="font-size:13.5px;">{{ $test->prompt }}</p>
    @if ($test->expected_output)
        <div class="cell-sub" style="margin-top:8px;">Expected: {{ $test->expected_output }}</div>
    @endif
</div>

<div class="grid-2" style="margin-bottom:16px;">
    @foreach ($test->results as $result)
    <div class="card card-pad">
        <div class="flex items-center gap-8" style="margin-bottom:10px;">
            <div class="thumb">{{ substr($result->model->name ?? '?', 0, 2) }}</div>
            <b>{{ $result->model->name ?? 'Unknown model' }}</b>
            <span class="mono" style="margin-left:auto; font-weight:700;">{{ number_format($result->overall_score, 1) }}</span>
        </div>

        <form action="{{ route('admin.testlab.results.update', $result->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="form-field" style="margin-bottom:10px;">
                <label>Response</label>
                <textarea class="input" name="response_text" rows="4" placeholder="Paste the model's response here...">{{ $result->response_text }}</textarea>
            </div>
            <div class="form-grid" style="margin-bottom:10px;">
                @foreach ([
                    'score_quality' => 'Quality', 'score_accuracy' => 'Accuracy',
                    'score_prompt_adherence' => 'Prompt Adherence', 'score_creativity' => 'Creativity',
                    'score_speed' => 'Speed',
                ] as $field => $label)
                <div class="form-field">
                    <label style="font-size:11.5px;">{{ $label }}</label>
                    <input class="input" type="number" min="0" max="100" name="{{ $field }}" value="{{ $result->{$field} }}">
                </div>
                @endforeach
            </div>
            <button type="submit" class="btn btn-secondary btn-sm" style="width:100%; justify-content:center;">Save Result</button>
        </form>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-head"><h3>Scores — Side by Side</h3></div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Model</th><th>Quality</th><th>Accuracy</th><th>Prompt Adherence</th><th>Creativity</th><th>Speed</th><th>Overall</th></tr></thead>
        <tbody>
        @foreach ($test->results->sortByDesc('overall_score') as $result)
        <tr>
            <td><b>{{ $result->model->name ?? '—' }}</b></td>
            <td><x-score-meter :value="$result->score_quality ?? 0" :segments="5"/></td>
            <td><x-score-meter :value="$result->score_accuracy ?? 0" :segments="5"/></td>
            <td><x-score-meter :value="$result->score_prompt_adherence ?? 0" :segments="5"/></td>
            <td><x-score-meter :value="$result->score_creativity ?? 0" :segments="5"/></td>
            <td><x-score-meter :value="$result->score_speed ?? 0" :segments="5"/></td>
            <td class="mono" style="font-weight:700;">{{ number_format($result->overall_score, 1) }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
