@extends('layouts.admin')
@section('title', 'Feature Flags')

@section('content')

<x-page-header title="Feature Flag Management" subtitle="Toggle experimental and rollout features" :breadcrumb="['System', 'Feature Flags']">
    <x-slot:actions><button class="btn btn-primary btn-sm"><i data-lucide="plus"></i> New Flag</button></x-slot:actions>
</x-page-header>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Feature</th><th>Description</th><th>Environment</th><th>Rollout</th><th>Created</th><th>Status</th></tr></thead>
        <tbody>
        @php
        $flags = [
            ['AI Test Lab','Side-by-side model prompt testing','Production',100,'Jun 2, 2026',true],
            ['AI Recommendations','Personalized tool recommendations','Staging',40,'Jul 18, 2026',false],
            ['New Comparison UI','Redesigned comparison builder flow','Staging',0,'Jul 30, 2026',false],
            ['Price Tracker','Automated price change alerts','Production',100,'May 14, 2026',true],
            ['Admin-only Mode','Restrict site to admin access','Production',0,'Jan 9, 2026',false],
        ];
        @endphp
        @foreach($flags as $f)
        <tr>
            <td><b>{{ $f[0] }}</b></td>
            <td class="text-sub">{{ $f[1] }}</td>
            <td><span class="badge badge-neutral">{{ $f[2] }}</span></td>
            <td style="width:140px;">
                <div class="flex items-center gap-8">
                    <div class="progress" style="flex:1;"><span style="width:{{ $f[3] }}%;"></span></div>
                    <span class="mono cell-sub">{{ $f[3] }}%</span>
                </div>
            </td>
            <td class="cell-sub">{{ $f[4] }}</td>
            <td>
                <div class="flex items-center gap-8" data-bs-toggle="modal" data-bs-target="#flagModal" style="cursor:pointer;">
                    <div class="switch {{ $f[5] ? 'is-on' : '' }}"><i></i></div>
                    <span style="font-size:12px; font-weight:600; color:{{ $f[5] ? 'var(--pos)' : 'var(--text-lo)' }};">{{ $f[5] ? 'ON' : 'OFF' }}</span>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

<div class="modal fade" id="flagModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--bg-elevated); border:1px solid var(--border); border-radius:var(--radius-lg);">
            <div class="modal-head">
                <h3 style="margin:0; font-size:15px;">Confirm Feature Flag Change</h3>
                <button class="icon-btn" data-bs-dismiss="modal"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <p class="text-sub" style="font-size:13px;">
                    This flag is marked critical. Changing it in Production may affect live users immediately.
                    Are you sure you want to continue?
                </p>
            </div>
            <div class="modal-foot">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary btn-sm">Confirm Change</button>
            </div>
        </div>
    </div>
</div>
@endsection
