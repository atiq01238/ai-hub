@extends('layouts.admin')
@section('title', 'Error Detail')

@section('content')

<x-page-header title="SourceFetchTimeoutException" subtitle="News Collection · Critical" :breadcrumb="['System', 'Error Monitoring', 'Detail']">
    <x-slot:actions>
        <button class="btn btn-secondary btn-sm"><i data-lucide="user-plus"></i> Assign</button>
        <button class="btn btn-secondary btn-sm"><i data-lucide="eye-off"></i> Ignore</button>
        <button class="btn btn-primary btn-sm"><i data-lucide="check"></i> Mark Resolved</button>
    </x-slot:actions>
</x-page-header>

<div class="grid-12">
    <div class="col-8">
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="section-title">Error Message</div>
            <div class="mono" style="font-size:12.5px; background:var(--surface-2); padding:12px 14px; border-radius:8px; color:var(--neg);">
                SourceFetchTimeoutException: Request to source "TechCrunch AI" timed out after 30000ms
            </div>
        </div>
        <div class="card card-pad">
            <div class="section-title">Stack Trace</div>
            <div class="mono" style="font-size:11.5px; background:var(--surface-2); padding:14px; border-radius:8px; line-height:1.8; color:var(--text-md); white-space:pre-wrap;">at NewsCollector::fetchSource(SourceFetcher.php:142)
at NewsCollector::runPipeline(NewsCollector.php:58)
at App\Jobs\CollectNewsJob::handle(CollectNewsJob.php:24)
at Illuminate\Queue\CallQueuedHandler::call(...)</div>
        </div>
    </div>
    <div class="col-4 card card-pad">
        <div class="section-title">Details</div>
        <div style="margin-bottom:12px;"><div class="cell-sub">Module</div><div style="font-weight:600;">News Collection</div></div>
        <div style="margin-bottom:12px;"><div class="cell-sub">Timestamp</div><div style="font-weight:600;">Aug 5, 2026 · 4:12 PM</div></div>
        <div style="margin-bottom:12px;"><div class="cell-sub">Frequency</div><div style="font-weight:600;">12x today · 89x this month</div></div>
        <div style="margin-bottom:16px;"><div class="cell-sub">Related Activity</div><div style="font-weight:600;">Source: TechCrunch AI</div></div>
        <div class="form-field"><label>Resolution Notes</label><textarea class="input" rows="4" placeholder="Add a note about this error..."></textarea></div>
    </div>
</div>
@endsection
