@extends('layouts.admin')

@section('title', 'Automation Monitor')

@section('content')
<x-page-header
    title="Automation Monitor"
    subtitle="Automatic RSS collection, duplicate detection and local AI processing"
    :breadcrumb="['AI Intelligence', 'Automation Monitor']"
/>

@if(session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">
        {{ $errors->first() }}
    </div>
@endif

<div class="kpi-grid" style="grid-template-columns:repeat(5,minmax(0,1fr));margin-bottom:16px;">
    <x-kpi-card icon="radio" label="Active Sources" value="{{ $activeSources }}" />
    <x-kpi-card icon="newspaper" label="News Today" value="{{ $newsToday }}" />
    <x-kpi-card icon="send" label="Published Today" value="{{ $publishedToday }}" />
    <x-kpi-card icon="alert-triangle" label="Source Errors" value="{{ $failedSources }}" />
    <x-kpi-card icon="clock" label="Last Fetch" value="{{ $lastFetch ? \Carbon\Carbon::parse($lastFetch)->diffForHumans() : 'Never' }}" />
</div>

<div style="display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,.65fr);gap:16px;margin-bottom:16px;">
    <div class="card card-pad">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px;">
            <div>
                <div style="font-size:16px;font-weight:700;margin-bottom:4px;">Automatic News Pipeline</div>
                <div class="text-sub">RSS Fetch → Duplicate Detection → AI Processing</div>
            </div>

            <span class="badge {{ $automationEnabled ? 'badge-pos' : 'badge-neutral' }}">
                {{ $automationEnabled ? '● AUTOMATION ON' : '○ AUTOMATION OFF' }}
            </span>
        </div>

        <form method="POST" action="{{ route('admin.system.automation-monitor.update') }}">
            @csrf
            @method('PUT')

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:end;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:650;margin-bottom:7px;">Automation Status</label>
                    <select name="automation_enabled" class="select" style="width:100%;">
                        <option value="1" @selected($automationEnabled)>Enabled — Fetch automatically</option>
                        <option value="0" @selected(!$automationEnabled)>Paused — Manual only</option>
                    </select>
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:650;margin-bottom:7px;">Fetch Frequency</label>
                    <select name="frequency_minutes" class="select" style="width:100%;">
                        @foreach($frequencyOptions as $minutes => $label)
                            <option value="{{ $minutes }}" @selected($frequencyMinutes === $minutes)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:14px;">
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i data-lucide="settings-2"></i> Save Automation Settings
                </button>

                <span class="text-sub" style="font-size:12px;">
                    @if($automationEnabled && $nextRunAt)
                        Next scheduled run: <b>{{ $nextRunAt->format('M d, Y · h:i A') }}</b>
                    @else
                        Automatic runs are paused.
                    @endif
                </span>
            </div>
        </form>
    </div>

    <div class="card card-pad">
        <div style="font-size:16px;font-weight:700;margin-bottom:4px;">Manual Control</div>
        <div class="text-sub" style="margin-bottom:16px;">Use this when you want the newest AI news immediately.</div>

        <form method="POST" action="{{ route('admin.system.automation-monitor.run-now') }}">
            @csrf
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                <i data-lucide="refresh-cw"></i> Fetch Latest News Now
            </button>
        </form>

        <div class="text-sub" style="font-size:12px;margin-top:10px;text-align:center;">
            Runs the same protected production pipeline.
        </div>
    </div>
</div>

<div class="card card-pad" style="margin-bottom:16px;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px;">
        <div style="font-weight:700;">Pipeline Health</div>
        @php
            $statusType = match($lastRunStatus) {
                'success' => 'badge-pos',
                'failed' => 'badge-neg',
                'running' => 'badge-warn',
                default => 'badge-neutral',
            };
        @endphp
        <span class="badge {{ $statusType }}">{{ strtoupper($lastRunStatus ?: 'never') }}</span>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;">
        <div>
            <div class="text-sub" style="font-size:11px;">LAST STARTED</div>
            <div style="font-weight:650;margin-top:4px;">{{ $lastRunStartedAt ? \Carbon\Carbon::parse($lastRunStartedAt)->diffForHumans() : 'Never' }}</div>
        </div>
        <div>
            <div class="text-sub" style="font-size:11px;">LAST FINISHED</div>
            <div style="font-weight:650;margin-top:4px;">{{ $lastRunFinishedAt ? \Carbon\Carbon::parse($lastRunFinishedAt)->diffForHumans() : 'Never' }}</div>
        </div>
        <div>
            <div class="text-sub" style="font-size:11px;">DURATION</div>
            <div style="font-weight:650;margin-top:4px;">{{ $lastRunDuration !== null ? $lastRunDuration . 's' : '—' }}</div>
        </div>
        <div>
            <div class="text-sub" style="font-size:11px;">FREQUENCY</div>
            <div style="font-weight:650;margin-top:4px;">{{ $frequencyOptions[$frequencyMinutes] ?? 'Every 1 Hour' }}</div>
        </div>
    </div>

    @if($lastRunMessage)
        <div class="text-sub" style="margin-top:14px;padding-top:12px;border-top:1px solid var(--border, #e5e7eb);">
            {{ \Illuminate\Support\Str::limit($lastRunMessage, 300) }}
        </div>
    @endif
</div>

<div class="card card-pad" style="margin-bottom:16px;">
    <div style="font-weight:700;margin-bottom:12px;">AI Processing</div>

    @if($processing['available'] ?? false)
        <div class="flex gap-12" style="flex-wrap:wrap;">
            <span class="badge badge-neutral">Pending: {{ $processing['pending'] ?? 0 }}</span>
            <span class="badge badge-warn">Processing: {{ $processing['processing'] ?? 0 }}</span>
            <span class="badge badge-pos">Processed: {{ $processing['processed'] ?? 0 }}</span>
            <span class="badge badge-neg">Failed: {{ $processing['failed'] ?? 0 }}</span>
        </div>
    @else
        <div class="text-sub">Local AI processing fields are not installed yet. RSS collection is still available.</div>
    @endif
</div>

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Last Fetched</th>
                    <th>Collected</th>
                    <th>Last Error</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sources as $source)
                    <tr>
                        <td>
                            <b>{{ $source->name }}</b>
                            <div class="cell-sub">{{ strtoupper($source->type ?? 'rss') }}</div>
                        </td>
                        <td>
                            <x-status-badge
                                status="{{ ucfirst($source->status ?? 'inactive') }}"
                                type="{{ ($source->status ?? 'inactive') === 'active' ? 'pos' : 'neutral' }}"
                            />
                        </td>
                        <td>
                            @if($source->last_fetched_at)
                                {{ $source->last_fetched_at->diffForHumans() }}
                            @elseif($source->last_success_at)
                                {{ $source->last_success_at->diffForHumans() }}
                            @else
                                Never
                            @endif
                        </td>
                        <td class="mono">{{ number_format($source->articles_collected ?? 0) }}</td>
                        <td class="text-sub" style="max-width:360px;">
                            {{ $source->last_error ? \Illuminate\Support\Str::limit($source->last_error, 90) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;padding:30px;">No sources configured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
