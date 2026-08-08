@extends('layouts.admin')
@section('title', 'Integrations')

@section('content')

<x-page-header title="Integrations" subtitle="UI only — no real integrations are connected" :breadcrumb="['System', 'Integrations']" />

<div class="grid-3">
    @php
    $integrations = [
        ['News APIs','newspaper','Connected','pos'],
        ['Search APIs','search','Connected','pos'],
        ['AI APIs','brain-circuit','Connected','pos'],
        ['Analytics','activity','Warning','warn'],
        ['Social Media','share-2','Not Connected','neutral'],
        ['Email','mail','Connected','pos'],
        ['Storage','hard-drive','Error','neg'],
    ];
    @endphp
    @foreach($integrations as $i)
    <div class="card card-pad">
        <div class="flex items-center justify-between" style="margin-bottom:14px;">
            <div class="kpi-icon"><i data-lucide="{{ $i[1] }}"></i></div>
            <span class="badge badge-{{ $i[3] }}">{{ $i[2] }}</span>
        </div>
        <b style="font-size:14px;">{{ $i[0] }}</b>
        <div class="flex gap-8" style="margin-top:14px;">
            <button class="btn btn-secondary btn-sm" style="flex:1; justify-content:center;">Configure</button>
            <button class="btn btn-ghost btn-sm" style="flex:1; justify-content:center;">Test Connection</button>
        </div>
    </div>
    @endforeach
</div>
@endsection
