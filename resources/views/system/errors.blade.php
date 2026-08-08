@extends('layouts.admin')
@section('title', 'Error Monitoring')

@section('content')

<x-page-header title="Error Monitoring" subtitle="5 unresolved errors requiring attention" :breadcrumb="['System', 'Error Monitoring']" />

<div class="kpi-grid" style="grid-template-columns:repeat(5,1fr);">
    <x-kpi-card icon="alert-triangle" label="Total Errors" value="342" />
    <x-kpi-card icon="octagon-alert" label="Critical" value="2" />
    <x-kpi-card icon="triangle-alert" label="Warnings" value="18" />
    <x-kpi-card icon="check-circle" label="Resolved" value="337" />
    <x-kpi-card icon="circle-dot" label="Unresolved" value="5" />
</div>

<div class="card">
    <div class="card-head"><h3>Error Log</h3></div>
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Error</th><th>Module</th><th>Severity</th><th>Frequency</th><th>First Seen</th><th>Last Seen</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @php
        $errors = [
            ['SourceFetchTimeoutException','News Collection','Critical','12x today','Aug 3','2 min ago','Open'],
            ['QueueWorkerMemoryLimitExceeded','Background Jobs','Critical','4x today','Aug 4','18 min ago','Open'],
            ['DuplicateKeyConstraintViolation','Database','Medium','31x this week','Jul 29','1 hr ago','Investigating'],
            ['ImageUploadValidationError','Media Library','Low','9x this week','Jul 30','3 hr ago','Open'],
            ['SearchIndexSyncDelay','Search','Medium','6x today','Aug 5','5 hr ago','Investigating'],
        ];
        @endphp
        @foreach($errors as $i => $e)
        <tr>
            <td><b class="mono" style="font-size:12.5px;">{{ $e[0] }}</b></td>
            <td class="text-sub">{{ $e[1] }}</td>
            <td><span class="badge badge-{{ $e[2]==='Critical'?'neg':($e[2]==='Medium'?'warn':'neutral') }}">{{ $e[2] }}</span></td>
            <td class="text-sub">{{ $e[3] }}</td>
            <td class="cell-sub">{{ $e[4] }}</td>
            <td class="cell-sub">{{ $e[5] }}</td>
            <td><x-status-badge :status="$e[6]" :type="$e[6]==='Open' ? 'neg' : 'warn'" /></td>
            <td>
                <div class="flex gap-8">
                    <a href="{{ url('/system/errors/'.($i+1)) }}" class="btn btn-ghost btn-sm">View</a>
                    <button class="btn btn-ghost btn-sm">Resolve</button>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
