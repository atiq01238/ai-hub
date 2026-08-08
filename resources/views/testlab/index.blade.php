@extends('layouts.admin')
@section('title', 'AI Test Lab')

@section('content')

<x-page-header title="AI Test Lab" subtitle="Run side-by-side prompt tests across models" :breadcrumb="['Comparison & Benchmarks', 'AI Test Lab']">
    <x-slot:actions><button class="btn btn-primary btn-sm"><i data-lucide="plus"></i> New Test</button></x-slot:actions>
</x-page-header>

<div class="tabs">
    <div class="tab is-active">Text</div>
    <div class="tab">Image</div>
    <div class="tab">Video</div>
    <div class="tab">Audio</div>
    <div class="tab">Coding</div>
    <div class="tab">Reasoning</div>
</div>

<div class="grid-12" style="margin-bottom:20px;">
    <div class="col-4 card card-pad">
        <div class="section-title">Create Test</div>
        <div class="form-field" style="margin-bottom:12px;"><label>Test Name</label><input class="input" placeholder="e.g. Long-context summarization"></div>
        <div class="form-field" style="margin-bottom:12px;"><label>Prompt</label><textarea class="input" rows="4" placeholder="Enter test prompt..."></textarea></div>
        <div class="form-field" style="margin-bottom:12px;"><label>Category</label><select class="select"><option>Reasoning</option><option>Summarization</option><option>Coding</option></select></div>
        <div class="form-field" style="margin-bottom:12px;"><label>Models</label>
            <div class="flex gap-8" style="flex-wrap:wrap;">
                <span class="toggle-pill is-on">GPT-5.2 Turbo</span>
                <span class="toggle-pill is-on">Claude Opus 4.8</span>
                <span class="toggle-pill">Gemini 3 Pro</span>
            </div>
        </div>
        <div class="form-field" style="margin-bottom:12px;"><label>Evaluation Criteria</label><input class="input" placeholder="Quality, Accuracy, Speed..."></div>
        <div class="form-field" style="margin-bottom:16px;"><label>Expected Output</label><textarea class="input" rows="2" placeholder="Optional reference answer"></textarea></div>
        <button class="btn btn-primary" style="width:100%; justify-content:center;"><i data-lucide="play"></i> Run Test</button>
    </div>

    <div class="col-8 card card-pad">
        <div class="section-title">Test Results — Models Side-by-Side</div>
        <div class="grid-2">
            <div class="card card-pad" style="background:var(--surface-2);">
                <div class="flex items-center gap-8" style="margin-bottom:10px;"><div class="thumb">GP</div><b>GPT-5.2 Turbo</b></div>
                <p class="text-sub" style="font-size:12.5px; line-height:1.6;">Generated response summarizing the input with strong structural clarity and correct factual grounding across all cited sections.</p>
            </div>
            <div class="card card-pad" style="background:var(--surface-2);">
                <div class="flex items-center gap-8" style="margin-bottom:10px;"><div class="thumb">CL</div><b>Claude Opus 4.8</b></div>
                <p class="text-sub" style="font-size:12.5px; line-height:1.6;">Response demonstrated stronger nuance handling and slightly more concise phrasing while preserving accuracy.</p>
            </div>
        </div>
        <div class="divider"></div>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Model</th><th>Quality</th><th>Accuracy</th><th>Prompt Adherence</th><th>Creativity</th><th>Speed</th><th>Overall</th></tr></thead>
            <tbody>
                <tr><td><b>GPT-5.2 Turbo</b></td><td><x-score-meter :value="90" :segments="5"/></td><td><x-score-meter :value="88" :segments="5"/></td><td><x-score-meter :value="92" :segments="5"/></td><td><x-score-meter :value="80" :segments="5"/></td><td><x-score-meter :value="95" :segments="5"/></td><td class="mono" style="font-weight:700;">89.0</td></tr>
                <tr><td><b>Claude Opus 4.8</b></td><td><x-score-meter :value="94" :segments="5"/></td><td><x-score-meter :value="91" :segments="5"/></td><td><x-score-meter :value="90" :segments="5"/></td><td><x-score-meter :value="86" :segments="5"/></td><td><x-score-meter :value="88" :segments="5"/></td><td class="mono" style="font-weight:700;">89.8</td></tr>
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
