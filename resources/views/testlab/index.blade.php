@extends('layouts.admin')
@section('title','AI Test Lab')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/final-polish.css') }}">
<link rel="stylesheet" href="{{ asset('css/pages/testlab-admin.css') }}">
@endpush
@section('content')
<div class="tl-admin-page">
    <x-page-header title="AI Test Lab" subtitle="Controlled model experiments · dynamic rubrics · multi-run verification" :breadcrumb="['Comparison & Benchmarks','AI Test Lab']" />

    @if(session('status'))<div class="alert alert-success tl-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
    @if($errors->any())<div class="alert alert-danger tl-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

    <section class="tl-stat-grid">
        <article><span><i data-lucide="flask-conical"></i></span><div><b>{{ number_format($stats['total']) }}</b><small>Total experiments</small></div></article>
        <article><span><i data-lucide="globe-2"></i></span><div><b>{{ number_format($stats['published']) }}</b><small>Published</small></div></article>
        <article><span><i data-lucide="file-clock"></i></span><div><b>{{ number_format($stats['drafts']) }}</b><small>Drafts</small></div></article>
        <article><span><i data-lucide="trophy"></i></span><div><b>{{ number_format($stats['results']) }}</b><small>Model aggregates</small></div></article>
        <article><span><i data-lucide="badge-check"></i></span><div><b>{{ number_format($stats['verified']) }}</b><small>Verified aggregates</small></div></article>
    </section>

    <section class="card tl-filter-card">
        <form method="GET" class="tl-filter-form">
            <label class="tl-search"><i data-lucide="search"></i><input class="input" name="q" value="{{ request('q') }}" placeholder="Search tests, prompts or descriptions..."></label>
            <select class="select" name="status"><option value="">All status</option><option value="draft" @selected(request('status')==='draft')>Draft</option><option value="published" @selected(request('status')==='published')>Published</option></select>
            <select class="select" name="category"><option value="">All categories</option>@foreach($categories as $name=>$meta)<option value="{{ $name }}" @selected(request('category')===$name)>{{ $name }}</option>@endforeach</select>
            <select class="select" name="difficulty"><option value="">All difficulty</option>@foreach($difficulties as $value=>$label)<option value="{{ $value }}" @selected(request('difficulty')===$value)>{{ $label }}</option>@endforeach</select>
            <button class="btn btn-secondary"><i data-lucide="filter"></i>Filter</button>
            @if(request()->query())<a href="{{ route('admin.testlab.index') }}" class="btn btn-ghost">Reset</a>@endif
        </form>
    </section>

    <div class="tl-admin-layout">
        <aside class="card tl-create-card">
            <div class="tl-card-heading"><span class="tl-kicker">NEW EXPERIMENT</span><h2>Create a controlled test</h2><p>Choose the test protocol first. Test Lab applies a test-specific rubric automatically; you can refine it before model runs begin.</p></div>
            <form action="{{ route('admin.testlab.store') }}" method="POST" data-testlab-create>
                @csrf
                <label class="tl-field"><span>Test name <b>*</b></span><input class="input" name="name" value="{{ old('name') }}" required placeholder="Laravel authentication debugging"></label>
                <label class="tl-field"><span>Short description</span><textarea class="textarea" name="short_description" rows="2" maxlength="500" placeholder="What this experiment measures and why it matters.">{{ old('short_description') }}</textarea></label>

                <div class="tl-two">
                    <label class="tl-field"><span>Test type <b>*</b></span><select class="select" name="test_type" required>@foreach($testTypes as $value=>$meta)<option value="{{ $value }}" @selected(old('test_type','reasoning')===$value)>{{ $meta['label'] }}</option>@endforeach</select></label>
                    <label class="tl-field"><span>Run protocol <b>*</b></span><select class="select" name="run_mode" required>@foreach($runModes as $value=>$meta)<option value="{{ $value }}" @selected(old('run_mode','quick')===$value)>{{ $meta['label'] }} · {{ $meta['runs'] }} run{{ $meta['runs']==1?'':'s' }}/model</option>@endforeach</select></label>
                </div>

                <div class="tl-two">
                    <label class="tl-field"><span>Category <b>*</b></span><select class="select" name="category" required>@foreach($categories as $name=>$meta)<option value="{{ $name }}" @selected(old('category','Reasoning')===$name)>{{ $name }}</option>@endforeach</select></label>
                    <label class="tl-field"><span>Difficulty <b>*</b></span><select class="select" name="difficulty" required>@foreach($difficulties as $value=>$label)<option value="{{ $value }}" @selected(old('difficulty','standard')===$value)>{{ $label }}</option>@endforeach</select></label>
                </div>

                <div class="tl-two">
                    <label class="tl-field"><span>Feature measured</span><select class="select" name="feature_id"><option value="">None</option>@foreach($features as $feature)<option value="{{ $feature->id }}" @selected((string)old('feature_id')===(string)$feature->id)>{{ $feature->name }}</option>@endforeach</select></label>
                    <label class="tl-field"><span>Use case</span><select class="select" name="use_case_id"><option value="">None</option>@foreach($useCases as $useCase)<option value="{{ $useCase->id }}" @selected((string)old('use_case_id')===(string)$useCase->id)>{{ $useCase->name }}</option>@endforeach</select></label>
                </div>

                <label class="tl-field"><span>Shared prompt <b>*</b></span><textarea class="textarea tl-prompt-input" name="prompt" rows="7" required placeholder="Paste the exact prompt every selected model receives.">{{ old('prompt') }}</textarea></label>

                <div class="tl-field">
                    <div class="tl-field-row"><span>Models <b>*</b></span><small data-model-count>0 selected · max {{ config('test_lab.model_limit',6) }}</small></div>
                    <label class="tl-model-search"><i data-lucide="search"></i><input type="search" placeholder="Filter models..." data-model-search></label>
                    <div class="tl-model-picker" data-model-picker data-max="{{ config('test_lab.model_limit',6) }}">
                        @foreach($models as $model)
                            <label data-model-option data-name="{{ strtolower($model->name.' '.($model->company?->name ?? '')) }}">
                                <input type="checkbox" name="model_ids[]" value="{{ $model->id }}" @checked(in_array($model->id,old('model_ids',[])))>
                                <img src="{{ $model->logo_url }}" alt="">
                                <span><b>{{ $model->name }}</b><small>{{ $model->company?->name ?: 'Provider not listed' }}</small></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <details class="tl-advanced">
                    <summary><span><i data-lucide="clipboard-list"></i>Answer key & methodology <small>Optional now · editable before runs</small></span><i data-lucide="chevron-down"></i></summary>
                    <div class="tl-advanced-body">
                        <label class="tl-field"><span>Expected output / answer key</span><textarea class="textarea" name="expected_output" rows="4">{{ old('expected_output') }}</textarea></label>
                        <label class="tl-field"><span>Evaluation note</span><input class="input" name="criteria" value="{{ old('criteria') }}" placeholder="Any human-evaluation guidance"></label>
                        <label class="tl-field"><span>Methodology note</span><textarea class="textarea" name="methodology" rows="4" placeholder="How the prompt is run, what stays constant, and how evidence is captured.">{{ old('methodology') }}</textarea></label>
                    </div>
                </details>

                <button class="btn btn-primary tl-full"><i data-lucide="plus"></i>Create Draft Test</button>
            </form>
        </aside>

        <main class="card tl-registry-card">
            <header class="tl-registry-head"><div><span class="tl-kicker">TEST REGISTRY</span><h2>Evaluation sessions</h2><p>Drafts stay private. Published experiments use aggregate model scores from completed controlled runs.</p></div><span class="tl-total">{{ number_format($tests->total()) }}</span></header>

            @forelse($tests as $test)
                <article class="tl-test-row">
                    <div class="tl-test-icon"><i data-lucide="flask-conical"></i></div>
                    <div class="tl-test-main">
                        <div class="tl-badges">
                            <span class="tl-status {{ $test->status }}">{{ ucfirst($test->status) }}</span>
                            @if($test->is_verified)<span class="tl-verified"><i data-lucide="badge-check"></i>Verified methodology</span>@endif
                            <span>{{ $test->testTypeLabel() }}</span>
                            <span>{{ $test->runModeLabel() }} · {{ $test->required_runs }}×</span>
                            <span>{{ $test->category ?: 'General' }}</span>
                        </div>
                        <a class="tl-test-title" href="{{ route('admin.testlab.show',$test->id) }}">{{ $test->name }}</a>
                        <p>{{ $test->short_description ?: \Illuminate\Support\Str::limit($test->prompt,135) }}</p>
                        <div class="tl-test-meta">
                            <span><i data-lucide="layers-3"></i>{{ $test->complete_results_count }}/{{ $test->results_count }} model aggregates complete</span>
                            <span><i data-lucide="badge-check"></i>{{ $test->verified_results_count }} verified</span>
                            @if($test->feature)<span><i data-lucide="sparkles"></i>{{ $test->feature->name }}</span>@endif
                            @if($test->useCase)<span><i data-lucide="target"></i>{{ $test->useCase->name }}</span>@endif
                            <span><i data-lucide="calendar-days"></i>{{ $test->created_at->format('M j, Y') }}</span>
                        </div>
                    </div>
                    <div class="tl-row-actions">
                        <a href="{{ route('admin.testlab.show',$test->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="settings-2"></i>Manage</a>
                        @if($test->status==='published')<a href="{{ route('testlab.show',$test) }}" target="_blank" class="icon-btn" title="Open public page"><i data-lucide="external-link"></i></a>@endif
                        <form action="{{ route('admin.testlab.destroy',$test->id) }}" method="POST" onsubmit="return confirm('Delete this test, all model aggregates, run records and uploaded evidence?')">@csrf @method('DELETE')<button class="icon-btn icon-btn--danger" title="Delete"><i data-lucide="trash-2"></i></button></form>
                    </div>
                </article>
            @empty
                <div class="tl-empty"><span><i data-lucide="flask-conical"></i></span><h3>No matching tests</h3><p>Create a controlled experiment or clear the filters.</p></div>
            @endforelse

            @if($tests->hasPages())<div class="tl-pagination">{{ $tests->links() }}</div>@endif
        </main>
    </div>
</div>
@endsection
@push('scripts')<script src="{{ asset('js/admin/testlab.js') }}"></script>@endpush
