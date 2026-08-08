@extends('layouts.admin')
@section('title', 'Reports')

@section('content')

<x-page-header title="Reports" subtitle="Content and user reports flagged by the community" :breadcrumb="['Users & Community', 'Reports']" />

<div class="tabs">
    <div class="tab is-active">All</div>
    <div class="tab">Content Reports</div>
    <div class="tab">User Reports</div>
    <div class="tab">Resolved</div>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Reported Item</th><th>Type</th><th>Reason</th><th>Reported By</th><th>Date</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @php
        $reports = [
            ['Review on "Midjourney" by j.reviewer_92','Review','Spam / promotional content','anon_884','Aug 5','Pending'],
            ['Comment on "GPT-5.2 Turbo Explained"','Comment','Harassment','m.dev@mail.com','Aug 4','Pending'],
            ['User: fake_account_212','User','Impersonation','sarah@aihub.io','Aug 3','Resolved'],
            ['Tool submission "ScamGPT"','Submission','Fraudulent tool','k.reviewer@mail.com','Aug 2','Resolved'],
        ];
        @endphp
        @foreach($reports as $r)
        <tr>
            <td><b>{{ $r[0] }}</b></td>
            <td><span class="badge badge-neutral">{{ $r[1] }}</span></td>
            <td class="text-sub">{{ $r[2] }}</td>
            <td class="text-sub">{{ $r[3] }}</td>
            <td class="cell-sub">{{ $r[4] }}</td>
            <td><x-status-badge :status="$r[5]" :type="$r[5]==='Resolved' ? 'pos' : 'warn'" /></td>
            <td>
                <div class="flex gap-8">
                    <button class="btn btn-secondary btn-sm">Review</button>
                    <button class="btn btn-ghost btn-sm">Dismiss</button>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
