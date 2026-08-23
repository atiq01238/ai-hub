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
    $capturedCount = $test->results->filter(fn($result) => filled($result->response_text))->count();
    $defaultStep = $capturedCount < $test->results->count() ? 3 : 4;
    $step = max(1, min(4, (int) request('step', $defaultStep)));
    $resultIds = $test->results->pluck('id')->values();
    $requestedResultId = (int) request('result', 0);
    $activeResult = $requestedResultId ? $test->results->firstWhere('id', $requestedResultId) : null;
    $activeResult ??= $test->results->first(fn($result) => blank($result->response_text));
    $activeResult ??= $test->results->first();
    $activeResultIndex = $activeResult ? $resultIds->search($activeResult->id) : false;
    $previousResultId = $activeResultIndex !== false && $activeResultIndex > 0 ? $resultIds[$activeResultIndex - 1] : null;
    $nextResultId = $activeResultIndex !== false && $activeResultIndex < ($resultIds->count() - 1) ? $resultIds[$activeResultIndex + 1] : null;
    $verifiedCount = $ranked->where('is_verified', true)->count();
@endphp
<div class="tl-admin-page tl-wizard-page">
    <x-page-header :title="$test->name" :subtitle="'Test Lab · '.$capturedCount.'/'.$test->results->count().' responses captured · '.$completeCount.' scored'" :breadcrumb="['Comparison & Benchmarks','AI Test Lab',$test->name]">
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
                <span class="tl-kicker">SIMPLE WORKFLOW</span>
                <h2>Run this test in 4 steps</h2>
                <p>Use the same prompt for every model, save the exact responses, score them consistently, then publish the ranking.</p>
            </div>
            <div class="tl-wizard-status">
                <span><b>{{ $capturedCount }}</b>/{{ $test->results->count() }} responses</span>
                <span><b>{{ $completeCount }}</b>/{{ $test->results->count() }} scored</span>
                <span><b>{{ $verifiedCount }}</b> verified</span>
            </div>
        </div>
        <nav class="tl-stepper" aria-label="Test Lab workflow">
            @foreach([
                1 => ['Setup','Choose what you are testing','settings-2'],
                2 => ['Prompt','Lock the prompt & answer key','file-text'],
                3 => ['Run models','Paste each real model response','play-circle'],
                4 => ['Score & publish','Score results and publish','trophy'],
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
                <div><span class="tl-kicker">STEP 1 OF 4</span><h2>Test setup</h2><p>Define the test and confirm which models will receive the same prompt.</p></div>
                <span class="tl-status {{ $test->status }}">{{ ucfirst($test->status) }}</span>
            </header>

            <form action="{{ route('admin.testlab.update',$test->id) }}" method="POST" class="tl-simple-form">@csrf @method('PUT')
                <input type="hidden" name="section" value="setup">
                <div class="tl-settings-grid">
                    <label class="tl-field tl-span-2"><span>Test name <b>*</b></span><input class="input" name="name" value="{{ old('name',$test->name) }}" required></label>
                    <label class="tl-field"><span>Category <b>*</b></span><select class="select" name="category" required>@foreach($categories as $name=>$meta)<option value="{{ $name }}" @selected(old('category',$test->category)===$name)>{{ $name }}</option>@endforeach</select></label>
                    <label class="tl-field"><span>Difficulty <b>*</b></span><select class="select" name="difficulty">@foreach($difficulties as $value=>$label)<option value="{{ $value }}" @selected(old('difficulty',$test->difficulty)===$value)>{{ $label }}</option>@endforeach</select></label>
                    <label class="tl-field"><span>Feature measured</span><select class="select" name="feature_id"><option value="">None</option>@foreach($features as $feature)<option value="{{ $feature->id }}" @selected((string)old('feature_id',$test->feature_id)===(string)$feature->id)>{{ $feature->name }}</option>@endforeach</select></label>
                    <label class="tl-field"><span>Use case</span><select class="select" name="use_case_id"><option value="">None</option>@foreach($useCases as $useCase)<option value="{{ $useCase->id }}" @selected((string)old('use_case_id',$test->use_case_id)===(string)$useCase->id)>{{ $useCase->name }}</option>@endforeach</select></label>
                    <label class="tl-field tl-span-2"><span>Short description</span><textarea class="textarea" name="short_description" rows="3" maxlength="500" placeholder="One sentence explaining what this test measures.">{{ old('short_description',$test->short_description) }}</textarea></label>
                </div>
                <div class="tl-step-actions"><a class="btn btn-secondary" href="{{ route('admin.testlab.index') }}">Back to Test Lab</a><button class="btn btn-primary"><i data-lucide="arrow-right"></i>Save & Continue to Prompt</button></div>
            </form>

            <div class="tl-selected-models">
                <div class="tl-panel-subhead"><div><span class="tl-kicker">SELECTED MODELS</span><h3>{{ $test->results->count() }} models in this test</h3></div>
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
                    <article class="tl-model-chip">
                        <img src="{{ $result->model?->logo_url ?: asset('favicon.ico') }}" alt="">
                        <div><b>{{ $result->model?->name ?? 'Unknown model' }}</b><small>{{ $result->model?->company?->name ?: 'Provider not listed' }}</small></div>
                        <span>{{ filled($result->response_text) ? 'Response saved' : 'Not run yet' }}</span>
                    </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($step === 2)
        <section class="card tl-wizard-panel">
            <header class="tl-panel-head"><div><span class="tl-kicker">STEP 2 OF 4</span><h2>Lock the shared prompt</h2><p>This exact prompt should be sent to every selected model without changing wording between runs.</p></div></header>
            <form action="{{ route('admin.testlab.update',$test->id) }}" method="POST" class="tl-simple-form">@csrf @method('PUT')
                <input type="hidden" name="section" value="prompt">
                <label class="tl-field"><span>Shared prompt <b>*</b></span><textarea class="textarea tl-prompt-editor" name="prompt" rows="14" required data-shared-prompt>{{ old('prompt',$test->prompt) }}</textarea></label>
                <div class="tl-copy-row"><button type="button" class="btn btn-secondary btn-sm" data-copy-prompt><i data-lucide="copy"></i>Copy prompt</button><small>Use this same text in GPT, Claude, Gemini and every other model in the test.</small></div>
                <div class="tl-two">
                    <label class="tl-field"><span>Expected output / answer key</span><textarea class="textarea" name="expected_output" rows="6" placeholder="Write the correct answer or the properties a correct response must contain.">{{ old('expected_output',$test->expected_output) }}</textarea></label>
                    <label class="tl-field"><span>Methodology</span><textarea class="textarea" name="methodology" rows="6" placeholder="Explain how each model is run fairly: fresh chat, same prompt, no retries, how latency is measured, etc.">{{ old('methodology',$test->methodology) }}</textarea></label>
                </div>
                <label class="tl-field"><span>Evaluation note</span><input class="input" name="criteria" value="{{ old('criteria',$test->criteria) }}" placeholder="Optional note about what matters most in this test."></label>

                <details class="tl-advanced tl-score-settings" {{ $errors->has('weights') ? 'open' : '' }}>
                    <summary><span><i data-lucide="sliders-horizontal"></i>Scoring weights <small>Current total: <b data-weight-total>100%</b></small></span><i data-lucide="chevron-down"></i></summary>
                    <div class="tl-advanced-body">
                        <p class="tl-help-text">Leave the defaults unless this test needs a different emphasis. The total must remain exactly 100%.</p>
                        <div class="tl-weight-grid tl-weight-grid-wide" data-weight-grid>
                            @foreach($criteria as $key=>$criterion)
                                <label><span>{{ $criterion['label'] }}</span><small>{{ $criterion['description'] }}</small><div><input type="number" min="0" max="100" name="weights[{{ $key }}]" value="{{ old('weights.'.$key,$weights[$key] ?? 0) }}"><em>%</em></div></label>
                            @endforeach
                        </div>
                        <div class="tl-weight-total">Total (must equal 100%): <b data-weight-total>100%</b></div>
                    </div>
                </details>

                <div class="tl-step-actions"><a class="btn btn-secondary" href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>1]) }}"><i data-lucide="arrow-left"></i>Setup</a><button class="btn btn-primary"><i data-lucide="arrow-right"></i>Save & Start Model Runs</button></div>
            </form>
        </section>
    @endif

    @if($step === 3)
        <section class="card tl-wizard-panel">
            <header class="tl-panel-head">
                <div><span class="tl-kicker">STEP 3 OF 4</span><h2>Run each model</h2><p>Open each provider, paste the same prompt, then paste the exact response back here. Scores come in the next step.</p></div>
                <span class="tl-progress-pill">{{ $capturedCount }}/{{ $test->results->count() }} captured</span>
            </header>

            <div class="tl-run-prompt">
                <div><span>Shared prompt</span><button type="button" class="btn btn-secondary btn-sm" data-copy-prompt data-copy-source="#tl-locked-prompt"><i data-lucide="copy"></i>Copy prompt</button></div>
                <pre id="tl-locked-prompt">{{ $test->prompt }}</pre>
            </div>

            <nav class="tl-model-tabs" aria-label="Models in this test">
                @foreach($test->results as $result)
                    <a href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>3,'result'=>$result->id]) }}" class="{{ $activeResult?->id === $result->id ? 'is-active' : '' }} {{ filled($result->response_text) ? 'is-done' : '' }}">
                        <img src="{{ $result->model?->logo_url ?: asset('favicon.ico') }}" alt=""><span><b>{{ $result->model?->name ?? 'Unknown model' }}</b><small>{{ filled($result->response_text) ? 'Response captured' : 'Waiting for response' }}</small></span>@if(filled($result->response_text))<i data-lucide="check-circle-2"></i>@endif
                    </a>
                @endforeach
            </nav>

            @if($activeResult)
            <article class="tl-run-card">
                <header>
                    <div class="tl-result-model"><img src="{{ $activeResult->model?->logo_url ?: asset('favicon.ico') }}" alt=""><div><span class="tl-kicker">NOW TESTING</span><h3>{{ $activeResult->model?->name ?? 'Unknown model' }}</h3><p>{{ $activeResult->model?->company?->name ?: 'Provider not listed' }}</p></div></div>
                    <span class="tl-run-number">{{ ($activeResultIndex === false ? 0 : $activeResultIndex) + 1 }} / {{ $test->results->count() }}</span>
                </header>
                <form action="{{ route('admin.testlab.results.update',$activeResult->id) }}" method="POST" enctype="multipart/form-data" class="tl-result-form tl-capture-form">@csrf @method('PUT')
                    <input type="hidden" name="section" value="capture">
                    <label class="tl-field"><span>Paste the exact {{ $activeResult->model?->name }} response <b>*</b></span><textarea class="textarea tl-response-editor" name="response_text" rows="14" required placeholder="Paste the model response exactly as returned. Do not rewrite or clean it up.">{{ old('response_text',$activeResult->response_text) }}</textarea></label>
                    <div class="tl-two">
                        <label class="tl-field"><span>Model version</span><input class="input" name="model_version" value="{{ old('model_version',$activeResult->model_version ?: $activeResult->model?->version) }}" placeholder="Optional"></label>
                        <label class="tl-field"><span>Tested at</span><input class="input" type="datetime-local" name="tested_at" value="{{ old('tested_at',$activeResult->tested_at?->format('Y-m-d\TH:i')) }}"></label>
                    </div>

                    <details class="tl-advanced tl-run-advanced">
                        <summary><span><i data-lucide="gauge"></i>Advanced run details <small>Optional</small></span><i data-lucide="chevron-down"></i></summary>
                        <div class="tl-advanced-body">
                            <div class="tl-result-meta-grid metrics">
                                <label class="tl-field"><span>Latency (ms)</span><input class="input" type="number" min="0" name="latency_ms" value="{{ old('latency_ms',$activeResult->latency_ms) }}"></label>
                                <label class="tl-field"><span>Input tokens</span><input class="input" type="number" min="0" name="input_tokens" value="{{ old('input_tokens',$activeResult->input_tokens) }}"></label>
                                <label class="tl-field"><span>Output tokens</span><input class="input" type="number" min="0" name="output_tokens" value="{{ old('output_tokens',$activeResult->output_tokens) }}"></label>
                                <label class="tl-field"><span>Estimated cost USD</span><input class="input" type="number" step="0.000001" min="0" name="estimated_cost_usd" value="{{ old('estimated_cost_usd',$activeResult->estimated_cost_usd) }}"></label>
                            </div>
                            <div class="tl-two">
                                <label class="tl-field"><span>Evidence/source label</span><input class="input" name="source_label" value="{{ old('source_label',$activeResult->source_label) }}" placeholder="Provider web app"></label>
                                <label class="tl-field"><span>Evidence/source URL</span><input class="input" type="url" name="source_url" value="{{ old('source_url',$activeResult->source_url) }}" placeholder="https://..."></label>
                            </div>
                            <label class="tl-field"><span>Evidence screenshot</span><input class="input" type="file" name="evidence_image" accept="image/png,image/jpeg,image/webp"></label>
                            @if($activeResult->evidence_url)<div class="tl-evidence-preview"><img src="{{ $activeResult->evidence_url }}" alt="Evidence for {{ $activeResult->model?->name }}"><label><input type="checkbox" name="remove_evidence" value="1"> Remove current evidence</label></div>@endif
                        </div>
                    </details>

                    <div class="tl-step-actions">
                        <div class="tl-step-actions-left">
                            @if($previousResultId)<a class="btn btn-secondary" href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>3,'result'=>$previousResultId]) }}"><i data-lucide="arrow-left"></i>Previous model</a>@else<a class="btn btn-secondary" href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>2]) }}"><i data-lucide="arrow-left"></i>Prompt</a>@endif
                        </div>
                        <button class="btn btn-primary" name="wizard_action" value="next"><i data-lucide="save"></i>{{ $nextResultId ? 'Save & Next Model' : 'Save & Review Scores' }}</button>
                    </div>
                </form>
            </article>
            @endif
        </section>
    @endif

    @if($step === 4)
        <section class="card tl-wizard-panel">
            <header class="tl-panel-head">
                <div><span class="tl-kicker">STEP 4 OF 4</span><h2>Review automatic scores</h2><p>Test Lab pre-fills suggested scores from the saved response, answer key and prompt rules. Review them, adjust only if needed, then save.</p></div>
                <span class="tl-progress-pill">{{ $completeCount }}/{{ $test->results->count() }} scored</span>
            </header>

            <div class="tl-score-review-list">
                @foreach($test->results as $result)
                @php($suggestion = $autoScores[$result->id] ?? null)
                @php($suggestedScores = $suggestion['scores'] ?? [])
                <article class="tl-score-review {{ $result->status }}">
                    <header>
                        <div class="tl-result-model"><img src="{{ $result->model?->logo_url ?: asset('favicon.ico') }}" alt=""><div><h3>{{ $result->model?->name ?? 'Unknown model' }}</h3><p>{{ $result->model?->company?->name ?: 'Provider not listed' }}</p></div></div>
                        <div class="tl-score-state"><span>{{ $result->status === 'complete' ? 'Saved score' : (filled($result->response_text) ? 'Auto-score ready' : 'Response missing') }}</span><b>{{ $result->status==='complete' ? number_format((float)$result->overall_score,1) : ($suggestion && $suggestion['overall'] !== null ? number_format((float)$suggestion['overall'],1) : '—') }}</b></div>
                    </header>

                    @if(blank($result->response_text))
                        <div class="tl-missing-response"><i data-lucide="circle-alert"></i><div><b>No response captured yet.</b><span>Run this model before scoring it.</span></div><a class="btn btn-secondary btn-sm" href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>3,'result'=>$result->id]) }}">Run model</a></div>
                    @else
                        <details class="tl-response-review">
                            <summary><span><i data-lucide="message-square-text"></i>View exact model response</span><i data-lucide="chevron-down"></i></summary>
                            <pre>{{ $result->response_text }}</pre>
                        </details>
                        @if($result->status !== 'complete' && $suggestion)
                            <div class="tl-auto-score-banner">
                                <div><i data-lucide="sparkles"></i><span><b>Automatic score suggested</b><small>{{ $suggestion['confidence'] }}% evaluator confidence · suggested overall {{ number_format((float)$suggestion['overall'],1) }}/100</small></span></div>
                                <details><summary>How was this scored?</summary><ul>@foreach($suggestion['signals'] as $label=>$signal)<li><b>{{ ucfirst($label) }}:</b> {{ $signal }}</li>@endforeach</ul></details>
                            </div>
                        @endif
                        <form action="{{ route('admin.testlab.results.update',$result->id) }}" method="POST" class="tl-score-form">@csrf @method('PUT')
                            <input type="hidden" name="section" value="score">
                            <input type="hidden" name="_result_id" value="{{ $result->id }}">
                            @php($restoreOldScore = (string) old('_result_id') === (string) $result->id)
                            <div class="tl-score-grid tl-score-grid-simple">
                                @foreach($criteria as $key=>$criterion)
                                    @php($field=$criterion['field'])
                                    @php($scoreValue = $restoreOldScore ? old($field) : ($result->{$field} ?? ($suggestedScores[$field] ?? '')))
                                    <label><span>{{ $criterion['label'] }} <small>{{ $weights[$key] ?? 0 }}%</small></span><input class="input" type="number" min="0" max="100" name="{{ $field }}" value="{{ $scoreValue }}" placeholder="0–100" required></label>
                                @endforeach
                            </div>
                            @php($summaryValue = $restoreOldScore ? old('evaluator_summary') : ($result->evaluator_summary ?: ($suggestion['summary'] ?? '')))
                            <label class="tl-field"><span>Evaluator summary</span><textarea class="textarea" name="evaluator_summary" rows="3" placeholder="Automatic evaluation summary will appear here when an answer key is available.">{{ $summaryValue }}</textarea></label>
                            <div class="tl-score-form-footer">
                                <label class="tl-check inline"><input type="hidden" name="is_verified" value="0"><input type="checkbox" name="is_verified" value="1" @checked($restoreOldScore ? old('is_verified') : $result->is_verified)><span><b>Verified result</b><small>I reviewed the saved response/evidence.</small></span></label>
                                <button class="btn btn-primary"><i data-lucide="check"></i>{{ $result->status==='complete' ? 'Update Score' : 'Accept & Save Score' }}</button>
                            </div>
                        </form>
                    @endif
                </article>
                @endforeach
            </div>
        </section>

        <section class="card tl-leaderboard-card tl-wizard-leaderboard">
            <header><div><span class="tl-kicker">LIVE RANKING</span><h2>Current leaderboard</h2></div><small>Weighted score /100</small></header>
            @if($ranked->isNotEmpty())
            <div class="table-wrap"><table class="data-table tl-table"><thead><tr><th>#</th><th>Model</th>@foreach($criteria as $criterion)<th>{{ $criterion['label'] }}</th>@endforeach<th>Overall</th><th>Verified</th></tr></thead><tbody>
                @foreach($ranked as $i=>$result)<tr><td><span class="tl-rank">{{ $i+1 }}</span></td><td><div class="tl-table-model"><img src="{{ $result->model?->logo_url ?: asset('favicon.ico') }}" alt=""><strong>{{ $result->model?->name ?? '—' }}</strong></div></td>@foreach($criteria as $criterion)@php($score=$result->{$criterion['field']})<td>{{ $score===null ? '—' : $score }}</td>@endforeach<td><strong>{{ number_format((float)$result->overall_score,1) }}</strong></td><td>{!! $result->is_verified ? '<span class="tl-verified"><i data-lucide="badge-check"></i>Yes</span>' : '—' !!}</td></tr>@endforeach
            </tbody></table></div>
            @else<div class="tl-empty small"><h3>Automatic suggestions are ready</h3><p>Review and accept at least two suggested scores above; saved results will then appear in the leaderboard.</p></div>@endif
        </section>

        <section class="card tl-publish-card">
            <header class="tl-panel-head"><div><span class="tl-kicker">PUBLICATION</span><h2>{{ $test->status === 'published' ? 'Update publication' : 'Ready to publish?' }}</h2><p>{{ $completeCount >= 2 ? 'The test has enough complete results to be published.' : 'Score at least two model results before publishing.' }}</p></div>@if($test->is_verified)<span class="tl-verified"><i data-lucide="badge-check"></i>Methodology verified</span>@endif</header>
            @if($completeCount < 2)<div class="tl-publish-note"><i data-lucide="info"></i><div><b>Still private</b><span>{{ 2 - $completeCount }} more complete result(s) required before this experiment can go public.</span></div></div>@endif
            <form action="{{ route('admin.testlab.update',$test->id) }}" method="POST" class="tl-publication-form">@csrf @method('PUT')
                <input type="hidden" name="section" value="publication">
                <label class="tl-field"><span>Source / verification note</span><input class="input" name="source_note" value="{{ old('source_note',$test->source_note) }}" placeholder="e.g. Outputs captured from provider web apps on the same date"></label>
                <div class="tl-two">
                    <label class="tl-check"><input type="hidden" name="is_featured" value="0"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured',$test->is_featured))><span><b>Featured experiment</b><small>Prioritize this test on the public Test Lab.</small></span></label>
                    <label class="tl-check"><input type="hidden" name="is_verified" value="0"><input type="checkbox" name="is_verified" value="1" @checked(old('is_verified',$test->is_verified))><span><b>Methodology verified</b><small>Enable only after setup and evidence have been reviewed.</small></span></label>
                </div>
                <details class="tl-advanced">
                    <summary><span><i data-lucide="search-check"></i>SEO details <small>Optional — defaults are generated automatically</small></span><i data-lucide="chevron-down"></i></summary>
                    <div class="tl-advanced-body tl-two">
                        <label class="tl-field"><span>SEO title</span><input class="input" maxlength="80" name="seo_title" value="{{ old('seo_title',$test->getRawOriginal('seo_title')) }}" placeholder="Leave blank for automatic title"></label>
                        <label class="tl-field"><span>Meta description</span><input class="input" maxlength="180" name="meta_description" value="{{ old('meta_description',$test->meta_description) }}" placeholder="Leave blank for automatic description"></label>
                    </div>
                </details>
                <div class="tl-step-actions">
                    <a class="btn btn-secondary" href="{{ route('admin.testlab.show',['id'=>$test->id,'step'=>3]) }}"><i data-lucide="arrow-left"></i>Back to model runs</a>
                    <div class="tl-publish-actions">
                        <button class="btn btn-secondary" name="status" value="draft"><i data-lucide="save"></i>Save as Draft</button>
                        <button class="btn btn-primary" name="status" value="published" @disabled($completeCount < 2)><i data-lucide="globe-2"></i>{{ $test->status === 'published' ? 'Update Published Test' : 'Publish Test' }}</button>
                    </div>
                </div>
            </form>
        </section>
    @endif
</div>
@endsection
@push('scripts')<script src="{{ asset('js/admin/testlab.js') }}"></script>@endpush
