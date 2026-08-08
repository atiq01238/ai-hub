@extends('layouts.admin')
@section('title', 'Tool Suggestions')

@section('content')

<x-page-header title="Tool Suggestions &amp; Submissions" subtitle="7 pending review" :breadcrumb="['Users & Community', 'Submissions']" />

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Tool Name</th><th>Submitted By</th><th>Website</th><th>Category</th><th>Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @php
        $subs = [
            ['NarrateAI','m.dev@mail.com','narrateai.io','Audio','Aug 5','Pending'],
            ['PixelForge','contact@pixelforge.app','pixelforge.app','Image Gen','Aug 4','Pending'],
            ['DevCopilot Pro','team@devcopilot.dev','devcopilot.dev','Coding','Aug 3','Approved'],
            ['ChatBrief','hello@chatbrief.io','chatbrief.io','Productivity','Aug 2','Rejected'],
        ];
        @endphp
        @foreach($subs as $s)
        <tr>
            <td><b>{{ $s[0] }}</b></td>
            <td class="text-sub">{{ $s[1] }}</td>
            <td class="text-sub">{{ $s[2] }}</td>
            <td class="text-sub">{{ $s[3] }}</td>
            <td class="cell-sub">{{ $s[4] }}</td>
            <td><x-status-badge :status="$s[5]" :type="$s[5]==='Approved' ? 'pos' : ($s[5]==='Rejected' ? 'neg' : 'warn')" /></td>
            <td>
                <div class="flex gap-8">
                    <button class="btn btn-secondary btn-sm"><i data-lucide="check"></i> Approve</button>
                    <button class="btn btn-ghost btn-sm"><i data-lucide="x"></i> Reject</button>
                    <button class="btn btn-ghost btn-sm"><i data-lucide="message-circle-question"></i> Request Info</button>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
