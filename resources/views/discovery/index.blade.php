@extends('layouts.admin')
@section('title','AI Discovery')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/discovery.css') }}">@endpush
@section('content')
<x-page-header title="AI Discovery" subtitle="Automatically surface new AI tools, models and meaningful product updates from your existing RSS intelligence pipeline." :breadcrumb="['AI Intelligence','AI Discovery']">
    <x-slot:actions><form method="POST" action="{{ route('admin.discovery.scan-now') }}" class="discovery-scan-form">@csrf<button class="btn btn-primary btn-sm"><i data-lucide="scan-search"></i> Scan Now</button></form><a class="btn btn-secondary btn-sm" href="{{ route('admin.system.news-sources') }}"><i data-lucide="rss"></i> Manage RSS Sources</a></x-slot:actions>
</x-page-header>
@if(session('status'))<div class="alert alert-success discovery-alert"><i data-lucide="circle-check"></i>{{ session('status') }}</div>@endif
@if(session('error'))<div class="alert alert-danger discovery-alert"><i data-lucide="triangle-alert"></i>{{ session('error') }}</div>@endif
<div class="discovery-stats">
@foreach([['Pending',$stats['pending'],'radar'],['Models',$stats['models'],'brain-circuit'],['Tools',$stats['tools'],'wrench'],['High confidence',$stats['high_confidence'],'badge-check']] as [$label,$value,$icon])
<div class="card discovery-stat"><span><i data-lucide="{{ $icon }}"></i></span><div><strong>{{ number_format($value) }}</strong><small>{{ $label }}</small></div></div>
@endforeach
</div>
<div class="card discovery-runtime {{ $runtime['health_status']==='attention' || $runtime['health_status']==='failed' ? 'needs-attention' : '' }}">
<div class="discovery-runtime-icon"><i data-lucide="activity"></i></div><div><strong>Discovery Automation</strong><p>{{ $runtime['health_message'] }}</p></div><div class="discovery-runtime-meta"><span><b>{{ number_format($runtime['enabled_sources']) }}</b> enabled sources</span><span><b>{{ number_format($runtime['unanalyzed']) }}</b> waiting</span><span>{{ $runtime['health_checked_at'] ? 'Checked '.\Illuminate\Support\Carbon::parse($runtime['health_checked_at'])->diffForHumans() : 'Health check pending' }}</span></div>
</div>
<div class="card discovery-filter-card">
<form method="GET" class="discovery-filters">
<div class="discovery-search"><i data-lucide="search"></i><input class="input" name="q" value="{{ request('q') }}" placeholder="Search candidate or headline..."></div>
<select class="select" name="type"><option value="">All types</option>@foreach(['model'=>'New model','tool'=>'New tool','model_update'=>'Model update','tool_update'=>'Tool update'] as $v=>$l)<option value="{{ $v }}" @selected(request('type')===$v)>{{ $l }}</option>@endforeach</select>
<select class="select" name="status">@foreach(['pending'=>'Pending','approved'=>'Approved','merged'=>'Merged','ignored'=>'Ignored'] as $v=>$l)<option value="{{ $v }}" @selected(request('status','pending')===$v)>{{ $l }}</option>@endforeach</select>
<select class="select" name="confidence"><option value="">Any confidence</option><option value="85" @selected(request('confidence')==='85')>85%+</option><option value="70" @selected(request('confidence')==='70')>70%+</option><option value="55" @selected(request('confidence')==='55')>55%+</option></select>
<button class="btn btn-primary btn-sm"><i data-lucide="filter"></i> Filter</button><a class="btn btn-ghost btn-sm" href="{{ route('admin.discovery.index') }}">Reset</a>
</form>
</div>
<div class="discovery-layout">
<section class="card discovery-list-card">
<div class="discovery-card-head"><div><span class="discovery-eyebrow">DISCOVERY INBOX</span><h2>Detected candidates</h2></div><span class="discovery-count">{{ $discoveries->total() }} records</span></div>
@forelse($discoveries as $item)
<article class="discovery-row">
<div class="discovery-type-icon is-{{ str_contains($item->entity_type,'model') ? 'model' : 'tool' }}"><i data-lucide="{{ str_contains($item->entity_type,'model') ? 'brain-circuit' : 'wrench' }}"></i></div>
<div class="discovery-main">
<div class="discovery-row-top"><div><span class="discovery-kind">{{ strtoupper(str_replace('_',' ',$item->entity_type)) }}</span><h3><a href="{{ route('admin.discovery.show',$item->id) }}">{{ $item->candidate_name }}</a></h3></div><span class="discovery-confidence {{ $item->confidence>=85?'is-high':($item->confidence>=70?'is-mid':'') }}">{{ $item->confidence }}%</span></div>
<p>{{ \Illuminate\Support\Str::limit($item->headline,150) }}</p>
<div class="discovery-meta"><span><i data-lucide="building-2"></i>{{ $item->company?->name ?? 'Unmatched company' }}</span><span><i data-lucide="rss"></i>{{ $item->newsSource?->name ?? 'Unknown source' }}</span><span><i data-lucide="clock-3"></i>{{ $item->created_at->diffForHumans() }}</span></div>
<div class="discovery-actions">
@if($item->status==='pending')
@if(in_array($item->entity_type,['model','model_update']))<form method="POST" action="{{ route('admin.discovery.model',$item->id) }}">@csrf<button class="btn btn-primary btn-xs"><i data-lucide="plus"></i>{{ $item->matched_model_id?'Open Model':'Create Model Draft' }}</button></form>@endif
@if(in_array($item->entity_type,['tool','tool_update']))<form method="POST" action="{{ route('admin.discovery.tool',$item->id) }}">@csrf<button class="btn btn-primary btn-xs"><i data-lucide="plus"></i>{{ $item->matched_tool_id?'Open Tool':'Create Tool Draft' }}</button></form>@endif
@if(str_contains($item->entity_type,'update'))<form method="POST" action="{{ route('admin.discovery.merge',$item->id) }}">@csrf<button class="btn btn-secondary btn-xs"><i data-lucide="git-merge"></i>Mark Update Reviewed</button></form>@endif
<form method="POST" action="{{ route('admin.discovery.ignore',$item->id) }}">@csrf<button class="btn btn-ghost btn-xs"><i data-lucide="eye-off"></i>Ignore</button></form>
@else<form method="POST" action="{{ route('admin.discovery.restore',$item->id) }}">@csrf<button class="btn btn-secondary btn-xs"><i data-lucide="rotate-ccw"></i>Restore to Pending</button></form>@endif
<a class="btn btn-ghost btn-xs" href="{{ route('admin.discovery.show',$item->id) }}"><i data-lucide="arrow-up-right"></i>Review</a>
</div></div></article>
@empty<div class="discovery-empty"><i data-lucide="radar"></i><h3>No discoveries match these filters</h3><p>New RSS entries are analyzed automatically after collection. You can also run <code>php artisan discovery:scan</code> once to analyze existing news.</p></div>@endforelse
@if($discoveries->hasPages())<div class="discovery-pagination">{{ $discoveries->links() }}</div>@endif
</section>
<aside class="card discovery-source-card"><div class="discovery-card-head"><div><span class="discovery-eyebrow">SOURCE CONTROL</span><h2>RSS discovery rules</h2></div></div><p class="discovery-source-intro">These settings control discovery only. They do not disable normal news collection.</p>
@forelse($sources as $source)<form method="POST" action="{{ route('admin.discovery.sources.update',$source->id) }}" class="discovery-source-item">@csrf @method('PUT')<div class="discovery-source-title"><strong>{{ $source->newsSource?->name ?? 'Deleted source' }}</strong><span>{{ $source->discoveries_count }} found</span></div><div class="discovery-source-checks"><label><input type="checkbox" name="enabled" value="1" @checked($source->enabled)> Enabled</label><label><input type="checkbox" name="trusted" value="1" @checked($source->trusted)> Trusted</label><label><input type="checkbox" name="detect_models" value="1" @checked($source->detect_models)> Models</label><label><input type="checkbox" name="detect_tools" value="1" @checked($source->detect_tools)> Tools</label></div><label class="discovery-threshold"><span>Minimum confidence</span><input type="number" min="30" max="95" name="minimum_confidence" value="{{ $source->minimum_confidence }}"><b>%</b></label><button class="btn btn-secondary btn-xs">Save source</button></form>@empty<div class="discovery-empty is-small"><p>No RSS sources configured.</p></div>@endforelse
</aside></div>
@endsection
