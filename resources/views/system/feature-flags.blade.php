@extends('layouts.admin')
@section('title', 'Feature Flags')

@section('content')

<x-page-header title="Feature Flag Management" subtitle="Toggle experimental and rollout features" :breadcrumb="['System', 'Feature Flags']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="card card-pad" style="margin-bottom:16px;">
    <form action="{{ route('admin.system.feature-flags.store') }}" method="POST" class="flex gap-8" style="flex-wrap:wrap; align-items:flex-end;">
        @csrf
        <div class="form-field"><label>Name</label><input class="input" name="name" placeholder="e.g. AI Test Lab" required></div>
        <div class="form-field" style="flex:1; min-width:200px;"><label>Description</label><input class="input" name="description" placeholder="What this flag controls"></div>
        <div class="form-field"><label>Environment</label>
            <select class="select" name="environment">
                <option value="staging">Staging</option>
                <option value="production">Production</option>
            </select>
        </div>
        <div class="form-field"><label>Rollout %</label><input class="input" type="number" name="rollout_percentage" value="0" min="0" max="100" style="width:80px;"></div>
        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> New Flag</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Feature</th><th>Description</th><th>Environment</th><th>Rollout</th><th>Created</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($flags as $flag)
        <tr>
            <td><b>{{ $flag->name }}</b></td>
            <td class="text-sub">{{ $flag->description ?? '—' }}</td>
            <td><span class="badge badge-neutral">{{ ucfirst($flag->environment) }}</span></td>
            <td style="width:140px;">
                <div class="flex items-center gap-8">
                    <div class="progress" style="flex:1;"><span style="width:{{ $flag->rollout_percentage }}%;"></span></div>
                    <span class="mono cell-sub">{{ $flag->rollout_percentage }}%</span>
                </div>
            </td>
            <td class="cell-sub">{{ $flag->created_at->format('M j, Y') }}</td>
            <td>
                <form action="{{ route('admin.system.feature-flags.toggle', $flag->id) }}" method="POST" onsubmit="{{ $flag->environment === 'production' ? "return confirm('This flag is in Production. Changing it may affect live users. Continue?')" : '' }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-8" style="background:none; border:none; cursor:pointer; padding:0;">
                        <div class="switch {{ $flag->is_enabled ? 'is-on' : '' }}"><i></i></div>
                        <span style="font-size:12px; font-weight:600; color:{{ $flag->is_enabled ? 'var(--pos)' : 'var(--text-lo)' }};">{{ $flag->is_enabled ? 'ON' : 'OFF' }}</span>
                    </button>
                </form>
            </td>
            <td>
                <form action="{{ route('admin.system.feature-flags.destroy', $flag->id) }}" method="POST" onsubmit="return confirm('Delete this flag?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="icon-btn" style="width:28px;height:28px;"><i data-lucide="trash-2" style="width:14px;height:14px;"></i></button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-sub" style="text-align:center; padding:32px;">No feature flags yet.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
