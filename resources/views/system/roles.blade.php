@extends('layouts.admin')
@section('title', 'Roles & Permissions')

@section('content')

<x-page-header title="Roles &amp; Permissions" subtitle="6 roles · visual permission matrix" :breadcrumb="['System', 'Roles & Permissions']">
    <x-slot:actions><button class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Role</button></x-slot:actions>
</x-page-header>

<div class="grid-3" style="margin-bottom:20px;">
    @foreach(['Super Admin'=>'#5b7fff','Admin'=>'#8b5cf6','Editor'=>'#22d3ee','Researcher'=>'#34d399','Reviewer'=>'#fbbf24','Moderator'=>'#f87171'] as $role => $color)
    <div class="card card-pad" style="border-left:3px solid {{ $color }};">
        <div class="flex items-center justify-between">
            <b style="font-size:14px;">{{ $role }}</b>
            <span class="cell-sub">{{ [3,2,5,4,12,6][($loop->index)] ?? 3 }} users</span>
        </div>
    </div>
    @endforeach
</div>

@php
$modules = ['AI Tools','AI Models','AI News','Comparisons','Pricing','Users','Reviews','Settings','Security'];
$actions = ['View','Add','Edit','Delete','Publish','Export'];
@endphp

<div class="card">
    <div class="card-head"><h3>Permission Matrix — Editor</h3><div class="card-head__sub">Toggle module-level permissions</div></div>
    <div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Module</th>
                @foreach($actions as $a)<th style="text-align:center;">{{ $a }}</th>@endforeach
            </tr>
        </thead>
        <tbody>
        @foreach($modules as $m)
        <tr>
            <td><b>{{ $m }}</b></td>
            @foreach($actions as $i => $a)
                @php $on = !in_array($a,['Delete']) && !($m==='Settings' && $a!=='View') && !($m==='Security'); @endphp
                <td style="text-align:center;">
                    <div class="switch {{ $on ? 'is-on' : '' }}" style="margin:0 auto;"><i></i></div>
                </td>
            @endforeach
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
