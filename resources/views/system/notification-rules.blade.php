@extends('layouts.admin')
@section('title','Notification Rules')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/administration.css') }}">@endpush
@section('content')
@php $enabled=$rules->where('enabled',true)->count(); @endphp
<div class="ad-page">
<x-page-header title="Notification Rules" subtitle="Control which real application events generate in-app administrator alerts." :breadcrumb="['System','Notification Rules']">
<x-slot:actions><a href="{{ route('admin.system.notifications') }}" class="btn btn-secondary"><i data-lucide="bell"></i>Notification Center</a></x-slot:actions>
</x-page-header>
@if(session('status'))<div class="alert alert-success ad-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
<section class="ad-kpis ad-kpis--three">
<article class="ad-kpi"><span><i data-lucide="list-checks"></i></span><div><small>Total Rules</small><strong>{{ $rules->count() }}</strong></div></article>
<article class="ad-kpi ad-kpi--green"><span><i data-lucide="bell-ring"></i></span><div><small>Enabled</small><strong>{{ $enabled }}</strong></div></article>
<article class="ad-kpi ad-kpi--violet"><span><i data-lucide="app-window"></i></span><div><small>Delivery Channel</small><strong>In-app</strong></div></article>
</section>
<section class="card ad-table-card"><header class="ad-card-head"><div><span class="ad-eyebrow">Alert Policy</span><h2>Event notification rules</h2><p>Each switch controls whether its application event creates an in-app alert.</p></div><span class="ad-count">{{ $enabled }}/{{ $rules->count() }} enabled</span></header>
@if($rules->count())<div class="ad-rule-list">@foreach($rules as $rule)<article><span class="ad-rule-icon"><i data-lucide="bell-dot"></i></span><div><strong>{{ $rule->label }}</strong><small>Channel: In-app administrator notification</small></div><form action="{{ route('admin.system.notification-rules.toggle',$rule->id) }}" method="POST">@csrf<button class="ad-switch-button" type="submit"><span class="ad-switch {{ $rule->enabled?'is-on':'' }}"><i></i></span><strong>{{ $rule->enabled?'ON':'OFF' }}</strong></button></form></article>@endforeach</div>@else<div class="ad-empty"><span><i data-lucide="bell-off"></i></span><h3>No alert rules configured</h3><p>Rules will appear here once notification triggers are seeded.</p></div>@endif
</section>
<section class="ad-boundary"><span><i data-lucide="mail-warning"></i></span><div><strong>Delivery scope: In-app only</strong><p>Email and SMS notification delivery are not wired into these rules yet. The Integrations page can show whether mail configuration exists, but this rules engine currently creates in-app notifications only.</p></div></section>
</div>
@endsection
