@extends('layouts.admin')
@section('title',$test->name)
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/final-polish.css') }}">
<link rel="stylesheet" href="{{ asset('css/pages/testlab-admin.css') }}">
@endpush
@section('content')
@php
    $ranked = $test->results->where('status','complete')->sortByDesc('overall_score')->values();
    $completeCount = $ranked->count();
    $allRuns = $test->results->flatMap(fn($result) => $result->runs)->sortBy(fn($run) => sprintf('%08d-%03d',$run->ai_test_result_id,$run->run_number))->values();
    $totalRuns = $allRuns->count();
    $capturedCount = $allRuns->filter(fn($run) => filled($run->response_text))->count();
    $scoredRunCount = $allRuns->where('status','complete')->count();
    $defaultStep = $capturedCount < $totalRuns ? 3 : 4;
    $step = max(1, min(4, (int) request('step', $defaultStep)));
    $runIds = $allRuns->pluck('id')->values();
    $requestedRunId = (int) request('run', 0);
    $activeRun = $requestedRunId ? $allRuns->firstWhere('id', $requestedRunId) : null;
    $activeRun ??= $allRuns->first(fn($run) => blank($run->response_text));
    $activeRun ??= $allRuns->first();
    $activeRunIndex = $activeRun ? $runIds->search($activeRun->id) : false;
    $previousRunId = $activeRunIndex !== false && $activeRunIndex > 0 ? $runIds[$activeRunIndex - 1] : null;
    $nextRunId = $activeRunIndex !== false && $activeRunIndex < ($runIds->count() - 1) ? $runIds[$activeRunIndex + 1] : null;
    $activeResult = $activeRun?->result;
    $verifiedCount = $ranked->filter(fn($result) => in_array($result->verification_level,['verified','high_confidence'],true))->count();
    $currentRubric = collect($rubric)->keyBy('key');
    $hasCapturedRuns = $capturedCount > 0;
@endphp
<div class="tl-admin-page tl-wizard-page">
    <x-page-header :title="$test->name" :subtitle="'Test Lab V3 · '.$capturedCount.'/'.$totalRuns.' runs captured · '.$scoredRunCount.'/'.$totalRuns.' runs scored · '.$completeCount.' model aggregates'" :breadcrumb="['Comparison & Benchmarks','AI Test Lab',$test->name]">
        <x-slot:actions>
            <a href="{{ route('admin.testlab.index') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Test Lab</a>
            <a href="{{ route('admin.testlab.export',$test->id) }}" class="btn btn-secondary"><i data-lucide="download"></i>Export CSV</a>
            @if($test->status==='published')<a href="{{ route('testlab.show',$test) }}" target="_blank" class="btn btn-primary"><i data-lucide="external-link"></i>Public Page</a>@endif
        </x-slot:actions>
    </x-page-header>

    @if(session('status'))<div class="alert alert-success tl-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
    @if($errors->any())<div class="alert alert-danger tl-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

    <section class="card tl-wizard-shell">
        <div class="tl-wizard-intro">
            <div>
                <span class="tl-kicker">CONTROLLED EVALUATION</span>
                <h2>{{ $test->testTypeLabel() }} · {{ $test->runModeLabel() }}</h2>
                <p>Every model receives the same locked prompt. Each run is scored against the same test-specific rubric; model rankings use the completed-run average.</p>
            </div>
            <div class="tl-wizard-status">
                <span><b>{{ $capturedCount }}</b>/{{ $totalRuns }} captured</span>
                <span><b>{{ $scoredRunCount }}</b>/{{ $totalRuns }} scored runs</span>
                <span><b>{{ $verifiedCount }}</b> verified models</span>
            </div>
        </div>
        <nav class="tl-stepper" aria-label="Test Lab workflow">
            @foreach([
                1 => ['Setup','Type, protocol & models','settings-2'],
                2 => ['Prompt & rubric','Lock evaluation protocol','file-lock-2'],
                3 => ['Run models','Capture every controlled run','play-circle'],
                4 => ['Score & publish','Review, aggregate & publish','trophy'],
            ] as $number => $item)
                <a href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>$number]) }}" class="tl-step {{ $step===$number ? 'is-active' : '' }} {{ $number < $step ? 'is-past' : '' }}">
                    <span class="tl-step-number">{{ $number }}</span>
                    <span><b>{{ $item[0] }}</b><small>{{ $item[1] }}</small></span>
                    <i data-lucide="{{ $item[2] }}"></i>
                </a>
            @endforeach
        </nav>
    </section>

    @if($step === 1)
        <section class="card tl-wizard-panel">
            <header class="tl-panel-head">
                <div><span class="tl-kicker">STEP 1 OF 4</span><h2>Test setup</h2><p>Choose the evaluation type and run protocol before capturing responses.</p></div>
                <span class="tl-status {{ $test->status }}">{{ ucfirst($test->status) }}</span>
            </header>

            <form action="{{ route('admin.testlab.update',$test->id) }}" method="POST" class="tl-simple-form">@csrf @method('PUT')
                <input type="hidden" name="section" value="setup">
                <div class="tl-settings-grid">
                    <label class="tl-field tl-span-2"><span>Test name <b>*</b></span><input class="input" name="name" value="{{ old('name',$test->name) }}" required></label>
                    <label class="tl-field"><span>Test type <b>*</b></span><select class="select" name="test_type" required @disabled($hasCapturedRuns)>@foreach($testTypes as $value=>$meta)<option value="{{ $value }}" @selected(old('test_type',$test->test_type)===$value)>{{ $meta['label'] }}</option>@endforeach</select>@if($hasCapturedRuns)<input type="hidden" name="test_type" value="{{ $test->test_type }}">@endif</label>
                    <label class="tl-field"><span>Run protocol <b>*</b></span><select class="select" name="run_mode" required @disabled($hasCapturedRuns)>@foreach($runModes as $value=>$meta)<option value="{{ $value }}" @selected(old('run_mode',$test->run_mode)===$value)>{{ $meta['label'] }} · {{ $meta['runs'] }} run{{ $meta['runs']==1?'':'s' }}/model</option>@endforeach</select>@if($hasCapturedRuns)<input type="hidden" name="run_mode" value="{{ $test->run_mode }}">@endif</label>
                    <label class="tl-field"><span>Category <b>*</b></span><select class="select" name="category" required>@foreach($categories as $name=>$meta)<option value="{{ $name }}" @selected(old('category',$test->category)===$name)>{{ $name }}</option>@endforeach</select></label>
                    <label class="tl-field"><span>Difficulty <b>*</b></span><select class="select" name="difficulty">@foreach($difficulties as $value=>$label)<option value="{{ $value }}" @selected(old('difficulty',$test->difficulty)===$value)>{{ $label }}</option>@endforeach</select></label>
                    <label class="tl-field"><span>Feature measured</span><select class="select" name="feature_id"><option value="">None</option>@foreach($features as $feature)<option value="{{ $feature->id }}" @selected((string)old('feature_id',$test->feature_id)===(string)$feature->id)>{{ $feature->name }}</option>@endforeach</select></label>
                    <label class="tl-field"><span>Use case</span><select class="select" name="use_case_id"><option value="">None</option>@foreach($useCases as $useCase)<option value="{{ $useCase->id }}" @selected((string)old('use_case_id',$test->use_case_id)===(string)$useCase->id)>{{ $useCase->name }}</option>@endforeach</select></label>
                    <label class="tl-field tl-span-2"><span>Short description</span><textarea class="textarea" name="short_description" rows="3" maxlength="500">{{ old('short_description',$test->short_description) }}</textarea></label>
                </div>
                @if($hasCapturedRuns)<div class="tl-protocol-lock"><i data-lucide="lock-keyhole"></i><div><b>Protocol locked by captured evidence</b><span>Test type and run mode can no longer change because doing so would invalidate existing model runs.</span></div></div>@endif
                <div class="tl-step-actions"><a class="btn btn-secondary" href="{{ route('admin.testlab.index') }}">Back to Test Lab</a><button class="btn btn-primary"><i data-lucide="arrow-right"></i>Save & Continue to Prompt</button></div>
            </form>

            <div class="tl-selected-models">
                <div class="tl-panel-subhead"><div><span class="tl-kicker">SELECTED MODELS</span><h3>{{ $test->results->count() }} models · {{ $test->required_runs }} run{{ $test->required_runs===1?'':'s' }} each</h3></div>
                    @if($models->isNotEmpty() && $test->results->count() < config('test_lab.model_limit',6))
                    <details class="tl-add-models">
                        <summary class="btn btn-secondary"><i data-lucide="plus"></i>Add models</summary>
                        <form action="{{ route('admin.testlab.models.add',$test->id) }}" method="POST">@csrf
                            <label class="tl-model-search"><i data-lucide="search"></i><input type="search" placeholder="Find another model..." data-model-search></label>
                            <div class="tl-model-picker compact" data-model-picker data-max="{{ config('test_lab.model_limit',6) - $test->results->count() }}">
                                @foreach($models as $model)<label data-model-option data-name="{{ strtolower($model->name.' '.($model->company?->name ?? '')) }}"><input type="checkbox" name="model_ids[]" value="{{ $model->id }}"><img src="{{ $model->logo_url }}" alt=""><span><b>{{ $model->name }}</b><small>{{ $model->company?->name }}</small></span></label>@endforeach
                            </div>
                            <button class="btn btn-primary tl-full">Add selected models</button>
                        </form>
                    </details>
                    @endif
                </div>
                <div class="tl-model-chip-grid">
                    @foreach($test->results as $result)
                    @php($modelCaptured = $result->runs->filter(fn($run)=>filled($run->response_text))->count())
                    <article class="tl-model-chip">
                        <img src="{{ $result->model?->logo_url ?: asset('favicon.ico') }}" alt="">
                        <div><b>{{ $result->model?->name ?? 'Unknown model' }}</b><small>{{ $result->model?->company?->name ?: 'Provider not listed' }}</small></div>
                        <span>{{ $modelCaptured }}/{{ $test->required_runs }} runs captured</span>
                    </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($step === 2)
        <section class="card tl-wizard-panel">
            <header class="tl-panel-head"><div><span class="tl-kicker">STEP 2 OF 4</span><h2>Lock prompt & evaluation rubric</h2><p>Only applicable criteria carry weight. Missing or irrelevant evidence is N/A—Test Lab never inserts neutral filler scores.</p></div>@if($test->prompt_locked_at)<span class="tl-lock-pill"><i data-lucide="lock-keyhole"></i>Locked {{ $test->prompt_locked_at->format('M j, H:i') }}</span>@endif</header>
            <form action="{{ route('admin.testlab.update',$test->id) }}" method="POST" class="tl-simple-form">@csrf @method('PUT')
                <input type="hidden" name="section" value="prompt">
                <label class="tl-field"><span>Shared prompt <b>*</b></span><textarea class="textarea tl-prompt-editor" name="prompt" rows="14" required data-shared-prompt @readonly($hasCapturedRuns)>{{ old('prompt',$test->prompt) }}</textarea></label>
                <div class="tl-copy-row"><button type="button" class="btn btn-secondary btn-sm" data-copy-prompt><i data-lucide="copy"></i>Copy prompt</button><small>{{ $hasCapturedRuns ? 'Prompt is read-only because run evidence already exists.' : 'This exact text will be locked for every model and every run.' }}</small></div>
                <div class="tl-two">
                    <label class="tl-field"><span>Expected output / answer key</span><textarea class="textarea" name="expected_output" rows="6" placeholder="For objective checks, store the correct answer or required facts." @readonly($hasCapturedRuns)>{{ old('expected_output',$test->expected_output) }}</textarea></label>
                    <label class="tl-field"><span>Methodology</span><textarea class="textarea" name="methodology" rows="6" placeholder="Fresh chat, same settings, no retries, evidence source, latency method, etc.">{{ old('methodology',$test->methodology) }}</textarea></label>
                </div>
                <label class="tl-field"><span>Human evaluation note</span><input class="input" name="criteria" value="{{ old('criteria',$test->criteria) }}" placeholder="Optional guidance for manual rubric scoring." @readonly($hasCapturedRuns)></label>

                <div class="tl-rubric-builder" data-rubric-builder data-rubric-locked="{{ $hasCapturedRuns ? 1 : 0 }}">
                    <div class="tl-rubric-head"><div><span class="tl-kicker">DYNAMIC RUBRIC</span><h3>{{ $test->testTypeLabel() }} scoring</h3><p>Enable only criteria that genuinely apply. Enabled weights must total 100%.</p></div><div class="tl-rubric-total">Total <b data-rubric-total>100%</b></div></div>
                    <div class="tl-rubric-grid">
                        @foreach($rubricLibrary as $key=>$definition)
                            @php($active = $currentRubric->get($key))
                            @php($enabled = old('rubric.'.$key.'.enabled', $active ? 1 : 0))
                            <label class="tl-rubric-item {{ $enabled ? 'is-enabled' : '' }}" data-rubric-item>
                                @if($hasCapturedRuns)
                                    <input type="hidden" name="rubric[{{ $key }}][enabled]" value="{{ $active ? 1 : 0 }}">
                                    <input type="hidden" name="rubric[{{ $key }}][weight]" value="{{ $active['weight'] ?? 0 }}">
                                @else
                                    <input type="hidden" name="rubric[{{ $key }}][enabled]" value="0">
                                @endif
                                <input class="tl-rubric-toggle" type="checkbox" name="rubric[{{ $key }}][enabled]" value="1" @checked($enabled) data-rubric-toggle @disabled($hasCapturedRuns)>
                                <span class="tl-rubric-copy"><b>{{ $definition['label'] }}</b><small>{{ $definition['description'] }}</small><em>{{ ($definition['auto_strategy'] ?? 'manual') === 'manual' ? 'Human scored' : 'Auto-assist + human review' }}</em></span>
                                <span class="tl-rubric-weight"><input class="input" type="number" min="0" max="100" name="rubric[{{ $key }}][weight]" value="{{ old('rubric.'.$key.'.weight',$active['weight'] ?? 0) }}" data-rubric-weight @disabled($hasCapturedRuns)><i>%</i></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="tl-step-actions"><a class="btn btn-secondary" href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>1]) }}"><i data-lucide="arrow-left"></i>Setup</a><button class="btn btn-primary"><i data-lucide="lock-keyhole"></i>Lock Protocol & Start Runs</button></div>
            </form>
        </section>
    @endif

    @if($step === 3)
        <section class="card tl-wizard-panel">
            <header class="tl-panel-head">
                <div><span class="tl-kicker">STEP 3 OF 4</span><h2>Capture controlled runs</h2><p>{{ $test->runModeLabel() }} requires {{ $test->required_runs }} run{{ $test->required_runs===1?'':'s' }} per model. Paste exact, unedited responses and attach evidence where available.</p></div>
                <span class="tl-progress-pill">{{ $capturedCount }}/{{ $totalRuns }} captured</span>
            </header>

            <div class="tl-run-prompt">
                <div><span>Locked shared prompt</span><button type="button" class="btn btn-secondary btn-sm" data-copy-prompt data-copy-source="#tl-locked-prompt"><i data-lucide="copy"></i>Copy prompt</button></div>
                <pre id="tl-locked-prompt">{{ $test->prompt }}</pre>
            </div>

            <nav class="tl-model-tabs tl-run-tabs" aria-label="Runs in this test">
                @foreach($test->results as $result)
                    @foreach($result->runs as $run)
                    <a href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>3,'run'=>$run->id]) }}" class="{{ $activeRun?->id === $run->id ? 'is-active' : '' }} {{ filled($run->response_text) ? 'is-done' : '' }}">
                        <img src="{{ $result->model?->logo_url ?: asset('favicon.ico') }}" alt=""><span><b>{{ $result->model?->name ?? 'Unknown model' }}</b><small>Run {{ $run->run_number }}/{{ $test->required_runs }} · {{ filled($run->response_text) ? 'captured' : 'waiting' }}</small></span>@if(filled($run->response_text))<i data-lucide="check-circle-2"></i>@endif
                    </a>
                    @endforeach
                @endforeach
            </nav>

            @if($activeRun && $activeResult)
            <article class="tl-run-card">
                <header>
                    <div class="tl-result-model"><img src="{{ $activeResult->model?->logo_url ?: asset('favicon.ico') }}" alt=""><div><span class="tl-kicker">NOW TESTING · RUN {{ $activeRun->run_number }}</span><h3>{{ $activeResult->model?->name ?? 'Unknown model' }}</h3><p>{{ $activeResult->model?->company?->name ?: 'Provider not listed' }}</p></div></div>
                    <span class="tl-run-number">{{ ($activeRunIndex === false ? 0 : $activeRunIndex) + 1 }} / {{ $totalRuns }}</span>
                </header>
                <form action="{{ route('admin.testlab.runs.update',$activeRun->id) }}" method="POST" enctype="multipart/form-data" class="tl-result-form tl-capture-form">@csrf @method('PUT')
                    <input type="hidden" name="section" value="capture">
                    <label class="tl-field"><span>Exact response <b>*</b></span><textarea class="textarea tl-response-editor" name="response_text" rows="14" required placeholder="Paste the model response exactly as returned. Do not rewrite or clean it up.">{{ old('response_text',$activeRun->response_text) }}</textarea></label>
                    <div class="tl-two">
                        <label class="tl-field"><span>Model version</span><input class="input" name="model_version" value="{{ old('model_version',$activeRun->model_version ?: $activeResult->model?->version) }}" placeholder="Optional"></label>
                        <label class="tl-field"><span>Tested at</span><input class="input" type="datetime-local" name="tested_at" value="{{ old('tested_at',$activeRun->tested_at?->format('Y-m-d\TH:i')) }}"></label>
                    </div>

                    <details class="tl-advanced tl-run-advanced">
                        <summary><span><i data-lucide="gauge"></i>Run evidence & performance <small>Optional but recommended for verification</small></span><i data-lucide="chevron-down"></i></summary>
                        <div class="tl-advanced-body">
                            <div class="tl-result-meta-grid metrics">
                                <label class="tl-field"><span>Latency (ms)</span><input class="input" type="number" min="0" name="latency_ms" value="{{ old('latency_ms',$activeRun->latency_ms) }}"></label>
                                <label class="tl-field"><span>Input tokens</span><input class="input" type="number" min="0" name="input_tokens" value="{{ old('input_tokens',$activeRun->input_tokens) }}"></label>
                                <label class="tl-field"><span>Output tokens</span><input class="input" type="number" min="0" name="output_tokens" value="{{ old('output_tokens',$activeRun->output_tokens) }}"></label>
                                <label class="tl-field"><span>Estimated cost USD</span><input class="input" type="number" step="0.000001" min="0" name="estimated_cost_usd" value="{{ old('estimated_cost_usd',$activeRun->estimated_cost_usd) }}"></label>
                            </div>
                            <div class="tl-two">
                                <label class="tl-field"><span>Evidence/source label</span><input class="input" name="source_label" value="{{ old('source_label',$activeRun->source_label) }}" placeholder="Provider web app"></label>
                                <label class="tl-field"><span>Evidence/source URL</span><input class="input" type="url" name="source_url" value="{{ old('source_url',$activeRun->source_url) }}" placeholder="https://..."></label>
                            </div>
                            <label class="tl-field"><span>Evidence screenshot</span><input class="input" type="file" name="evidence_image" accept="image/png,image/jpeg,image/webp"></label>
                            @if($activeRun->evidence_url)<div class="tl-evidence-preview"><img src="{{ $activeRun->evidence_url }}" alt="Evidence for {{ $activeResult->model?->name }} run {{ $activeRun->run_number }}"><label><input type="checkbox" name="remove_evidence" value="1"> Remove current evidence</label></div>@endif
                        </div>
                    </details>

                    <div class="tl-step-actions">
                        <div class="tl-step-actions-left">@if($previousRunId)<a class="btn btn-secondary" href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>3,'run'=>$previousRunId]) }}"><i data-lucide="arrow-left"></i>Previous run</a>@else<a class="btn btn-secondary" href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>2]) }}"><i data-lucide="arrow-left"></i>Prompt & rubric</a>@endif</div>
                        <button class="btn btn-primary" name="wizard_action" value="next"><i data-lucide="save"></i>{{ $nextRunId ? 'Save & Next Run' : 'Save & Review Scores' }}</button>
                    </div>
                </form>
            </article>
            @endif
        </section>
    @endif

    @if($step === 4)
        <section class="card tl-wizard-panel">
            <header class="tl-panel-head">
                <div><span class="tl-kicker">STEP 4 OF 4</span><h2>Score runs against the rubric</h2><p>Automatic checks fill only criteria they can actually measure. N/A auto-suggestions stay blank and require human rubric review—no neutral defaults are inserted.</p></div>
                <span class="tl-progress-pill">{{ $scoredRunCount }}/{{ $totalRuns }} scored</span>
            </header>

            <div class="tl-score-review-list">
                @foreach($test->results as $result)
                <section class="tl-model-score-group">
                    <div class="tl-model-score-head">
                        <div class="tl-result-model"><img src="{{ $result->model?->logo_url ?: asset('favicon.ico') }}" alt=""><div><h3>{{ $result->model?->name ?? 'Unknown model' }}</h3><p>{{ $result->model?->company?->name ?: 'Provider not listed' }}</p></div></div>
                        <div class="tl-aggregate-state"><span>{{ $result->run_count }}/{{ $test->required_runs }} completed runs</span><b>{{ $result->status==='complete' ? number_format((float)$result->overall_score,1) : 'Pending' }}</b>@if($result->status==='complete' && $result->run_count>1)<small>Range {{ number_format((float)$result->score_min,1) }}–{{ number_format((float)$result->score_max,1) }}</small>@endif</div>
                    </div>

                    @foreach($result->runs as $run)
                    @php($suggestion = $autoScores[$run->id] ?? null)
                    @php($suggestedScores = $suggestion['scores'] ?? [])
                    @php($runScores = is_array($run->score_breakdown) ? $run->score_breakdown : [])
                    @php($restoreOldScore = (string) old('_run_id') === (string) $run->id)
                    <article id="run-{{ $run->id }}" class="tl-score-review {{ $run->status }}">
                        <header>
                            <div><span class="tl-kicker">RUN {{ $run->run_number }} OF {{ $test->required_runs }}</span><b class="tl-run-label">{{ filled($run->response_text) ? 'Response captured' : 'Response missing' }}</b></div>
                            <div class="tl-score-state"><span>{{ $run->status === 'complete' ? $run->verificationLabel() : (filled($run->response_text) ? 'Rubric review required' : 'Not ready') }}</span><b>{{ $run->overall_score !== null ? number_format((float)$run->overall_score,1) : ($suggestion && $suggestion['overall'] !== null ? number_format((float)$suggestion['overall'],1) : '—') }}</b></div>
                        </header>

                        @if(blank($run->response_text))
                            <div class="tl-missing-response"><i data-lucide="circle-alert"></i><div><b>No response captured for this run.</b><span>Capture the exact model output before scoring it.</span></div><a class="btn btn-secondary btn-sm" href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>3,'run'=>$run->id]) }}">Capture run</a></div>
                        @else
                            <details class="tl-response-review">
                                <summary><span><i data-lucide="message-square-text"></i>View exact response & evidence</span><i data-lucide="chevron-down"></i></summary>
                                <pre>{{ $run->response_text }}</pre>
                            </details>
                            @if($suggestion)
                                <div class="tl-auto-score-banner">
                                    <div><i data-lucide="scan-search"></i><span><b>{{ $suggestion['is_partial'] ? 'Partial deterministic auto-check' : 'Automatic checks available' }}</b><small>{{ $suggestion['auto_count'] }} auto-scored · {{ $suggestion['manual_count'] }} human-review criteria · {{ $suggestion['confidence'] }}% signal confidence@if($suggestion['overall']!==null) · partial {{ number_format((float)$suggestion['overall'],1) }}/100@endif</small></span></div>
                                    <details><summary>See scoring signals and N/A reasons</summary><ul>@foreach($rubric as $criterion)<li><b>{{ $criterion['label'] }}:</b> {{ $suggestion['signals'][$criterion['key']] ?? 'Human review required.' }}</li>@endforeach</ul></details>
                                </div>
                            @endif
                            <form action="{{ route('admin.testlab.runs.update',$run->id) }}" method="POST" class="tl-score-form">@csrf @method('PUT')
                                <input type="hidden" name="section" value="score"><input type="hidden" name="_run_id" value="{{ $run->id }}">
                                <div class="tl-score-grid tl-score-grid-dynamic">
                                    @foreach($rubric as $criterion)
                                        @php($key=$criterion['key'])
                                        @php($scoreValue = $restoreOldScore ? old('scores.'.$key) : ($run->status==='complete' ? ($runScores[$key] ?? '') : ($runScores[$key] ?? ($suggestedScores[$key] ?? ''))))
                                        @php($naChecked = $restoreOldScore ? old('na.'.$key) : ($run->status==='complete' && !array_key_exists($key,$runScores)))
                                        <label class="tl-score-criterion"><span>{{ $criterion['label'] }} <small>{{ $criterion['weight'] }}%</small></span><small class="tl-score-help">{{ $criterion['description'] }}</small><input class="input" type="number" min="0" max="100" name="scores[{{ $key }}]" value="{{ $scoreValue }}" placeholder="{{ ($suggestedScores[$key] ?? null) === null ? 'Human score 0–100' : '0–100' }}" data-score-input><span class="tl-na-option"><input type="hidden" name="na[{{ $key }}]" value="0"><input type="checkbox" name="na[{{ $key }}]" value="1" @checked($naChecked) data-score-na> N/A — exclude this criterion for this run</span></label>
                                    @endforeach
                                </div>
                                @php($summaryValue = $restoreOldScore ? old('evaluator_summary') : ($run->evaluator_summary ?: ($suggestion['summary'] ?? '')))
                                <label class="tl-field"><span>Evaluator summary</span><textarea class="textarea" name="evaluator_summary" rows="3" placeholder="Explain the important scoring decisions, especially manual rubric dimensions.">{{ $summaryValue }}</textarea></label>
                                <div class="tl-score-form-footer">
                                    <label class="tl-field tl-verification-select"><span>Run review level</span><select class="select" name="verification_level"><option value="unverified" @selected(($restoreOldScore?old('verification_level'):$run->verification_level)==='unverified')>Unverified</option><option value="reviewed" @selected(($restoreOldScore?old('verification_level'):$run->verification_level)==='reviewed')>Reviewed</option><option value="verified" @selected(($restoreOldScore?old('verification_level'):$run->verification_level)==='verified')>Verified · response/evidence checked</option></select></label>
                                    <button class="btn btn-primary"><i data-lucide="check"></i>{{ $run->status==='complete' ? 'Update Run Score' : 'Save Run Score' }}</button>
                                </div>
                            </form>
                        @endif
                    </article>
                    @endforeach
                </section>
                @endforeach
            </div>
        </section>

        <section class="card tl-leaderboard-card tl-wizard-leaderboard">
            <header><div><span class="tl-kicker">LIVE AGGREGATE RANKING</span><h2>Current model leaderboard</h2></div><small>Average across required completed runs</small></header>
            @if($ranked->isNotEmpty())
            <div class="table-wrap"><table class="data-table tl-table"><thead><tr><th>#</th><th>Model</th><th>Runs</th>@foreach($rubric as $criterion)<th>{{ $criterion['label'] }}</th>@endforeach<th>Overall</th><th>Confidence</th></tr></thead><tbody>
                @foreach($ranked as $i=>$result)
                @php($scores=$result->scores())
                <tr><td><span class="tl-rank">{{ $i+1 }}</span></td><td><div class="tl-table-model"><img src="{{ $result->model?->logo_url ?: asset('favicon.ico') }}" alt=""><strong>{{ $result->model?->name ?? '—' }}</strong></div></td><td>{{ $result->run_count }}/{{ $test->required_runs }}</td>@foreach($rubric as $criterion)<td>{{ isset($scores[$criterion['key']]) ? number_format((float)$scores[$criterion['key']],1) : '—' }}</td>@endforeach<td><strong>{{ number_format((float)$result->overall_score,1) }}</strong>@if($result->run_count>1)<small class="tl-range">{{ number_format((float)$result->score_min,1) }}–{{ number_format((float)$result->score_max,1) }}</small>@endif</td><td><span class="tl-verification-badge {{ $result->verification_level }}">{{ $result->verificationLabel() }}</span></td></tr>
                @endforeach
            </tbody></table></div>
            @else<div class="tl-empty small"><h3>No complete model aggregates yet</h3><p>Every model needs {{ $test->required_runs }} scored run{{ $test->required_runs===1?'':'s' }} before it enters this ranking.</p></div>@endif
        </section>

        <section class="card tl-publish-card">
            <header class="tl-panel-head"><div><span class="tl-kicker">PUBLICATION</span><h2>{{ $test->status === 'published' ? 'Update publication' : 'Ready to publish?' }}</h2><p>{{ $completeCount >= 2 ? 'The test has enough complete model aggregates to be published.' : 'Complete the required runs for at least two models before publishing.' }}</p></div>@if($test->is_verified)<span class="tl-verified"><i data-lucide="badge-check"></i>Methodology verified</span>@endif</header>
            @if($completeCount < 2)<div class="tl-publish-note"><i data-lucide="info"></i><div><b>Still private</b><span>{{ 2 - $completeCount }} more complete model aggregate(s) required before this experiment can go public.</span></div></div>@endif
            <form action="{{ route('admin.testlab.update',$test->id) }}" method="POST" class="tl-publication-form">@csrf @method('PUT')
                <input type="hidden" name="section" value="publication">
                <label class="tl-field"><span>Source / verification note</span><input class="input" name="source_note" value="{{ old('source_note',$test->source_note) }}" placeholder="e.g. Outputs captured from provider web apps using fresh chats on the same date"></label>
                <div class="tl-two">
                    <label class="tl-check"><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured',$test->is_featured))><span><b>Featured experiment</b><small>Prioritize this test on the public Test Lab.</small></span></label>
                    <label class="tl-check"><input type="hidden" name="is_verified" value="0"><input type="checkbox" name="is_verified" value="1" @checked(old('is_verified',$test->is_verified))><span><b>Methodology verified</b><small>Requires at least two verified model aggregates.</small></span></label>
                </div>
                <details class="tl-advanced"><summary><span><i data-lucide="search-check"></i>SEO details <small>Optional</small></span><i data-lucide="chevron-down"></i></summary><div class="tl-advanced-body tl-two"><label class="tl-field"><span>SEO title</span><input class="input" maxlength="80" name="seo_title" value="{{ old('seo_title',$test->getRawOriginal('seo_title')) }}"></label><label class="tl-field"><span>Meta description</span><input class="input" maxlength="180" name="meta_description" value="{{ old('meta_description',$test->meta_description) }}"></label></div></details>
                <div class="tl-step-actions"><a class="btn btn-secondary" href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>3]) }}"><i data-lucide="arrow-left"></i>Back to runs</a><div class="tl-publish-actions"><button class="btn btn-secondary" name="status" value="draft"><i data-lucide="save"></i>Save as Draft</button><button class="btn btn-primary" name="status" value="published" @disabled($completeCount < 2)><i data-lucide="globe-2"></i>{{ $test->status === 'published' ? 'Update Published Test' : 'Publish Test' }}</button></div></div>
            </form>
        </section>
    @endif
</div>
@endsection
@push('scripts')<script src="{{ asset('js/admin/testlab.js') }}"></script>@endpush
