@extends('layouts.admin')
@section('title', 'Backup & Restore')

@section('content')

<x-page-header title="Backup &amp; Restore Center" subtitle="UI only — no backend backup jobs are executed" :breadcrumb="['System', 'Backups']">
    <x-slot:actions><button class="btn btn-primary btn-sm"><i data-lucide="database-backup"></i> Create Backup</button></x-slot:actions>
</x-page-header>

<div class="kpi-grid" style="grid-template-columns:repeat(4,1fr);">
    <x-kpi-card icon="clock" label="Last Backup" value="2 hr ago" />
    <x-kpi-card icon="calendar-clock" label="Next Scheduled" value="Tonight, 2 AM" />
    <x-kpi-card icon="hard-drive" label="Storage Used" value="42.8 GB" />
    <x-kpi-card icon="layers" label="Backup Count" value="128" />
</div>

<div class="card">
    <div class="card-head"><h3>Backup History</h3></div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Backup Name</th><th>Date</th><th>Size</th><th>Type</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @php
        $backups = [
            ['Full Backup — Aug 5','Aug 5, 2:00 AM','4.2 GB','Full','Completed'],
            ['Database Backup — Aug 5','Aug 5, 12:00 PM','1.1 GB','Database','Completed'],
            ['Media Backup — Aug 4','Aug 4, 2:00 AM','3.8 GB','Media','Completed'],
            ['Full Backup — Aug 4','Aug 4, 2:00 AM','4.1 GB','Full','Failed'],
        ];
        @endphp
        @foreach($backups as $b)
        <tr>
            <td><b>{{ $b[0] }}</b></td>
            <td class="cell-sub">{{ $b[1] }}</td>
            <td class="mono">{{ $b[2] }}</td>
            <td><span class="badge badge-neutral">{{ $b[3] }}</span></td>
            <td><x-status-badge :status="$b[4]" :type="$b[4]==='Completed' ? 'pos' : 'neg'" /></td>
            <td>
                <div class="flex gap-8">
                    <button class="icon-btn" style="width:28px;height:28px;"><i data-lucide="download" style="width:14px;height:14px;"></i></button>
                    <button class="icon-btn" style="width:28px;height:28px;" data-bs-toggle="modal" data-bs-target="#restoreModal"><i data-lucide="history" style="width:14px;height:14px;"></i></button>
                    <button class="icon-btn" style="width:28px;height:28px;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--bg-elevated); border:1px solid var(--border); border-radius:var(--radius-lg);">
            <div class="modal-head">
                <h3 style="margin:0; font-size:15px;">Restore Backup</h3>
                <button class="icon-btn" data-bs-dismiss="modal"><i data-lucide="x"></i></button>
            </div>
            <div class="modal-body">
                <p class="text-sub" style="font-size:13px;">Restoring will overwrite current data with the selected backup. This cannot be undone.</p>
                <div class="flex gap-8" style="flex-wrap:wrap; margin-top:12px;">
                    <span class="toggle-pill is-on">Full Backup</span>
                    <span class="toggle-pill">Database Only</span>
                    <span class="toggle-pill">Media Only</span>
                    <span class="toggle-pill">Configuration Only</span>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger btn-sm"><i data-lucide="history"></i> Restore Backup</button>
            </div>
        </div>
    </div>
</div>
@endsection
