@extends('layouts.admin')
@section('title', 'Activity Logs')

@section('content')

<x-page-header title="Activity Logs" subtitle="Full audit trail of admin actions" :breadcrumb="['System', 'Activity Logs']">
    <x-slot:actions><button class="btn btn-secondary btn-sm"><i data-lucide="download"></i> Export CSV</button></x-slot:actions>
</x-page-header>

<div class="filter-bar">
    <select class="select"><option>All Users</option><option>Sarah Ahmed</option><option>Imran Khan</option></select>
    <select class="select"><option>All Actions</option><option>Create</option><option>Update</option><option>Delete</option><option>Publish</option></select>
    <select class="select"><option>All Modules</option><option>Pricing</option><option>AI Tools</option><option>News</option><option>Users</option></select>
    <select class="select"><option>All Dates</option><option>Today</option><option>7 Days</option></select>
    <select class="select"><option>All Status</option><option>Success</option><option>Failed</option></select>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>User</th><th>Action</th><th>Module</th><th>Description</th><th>Date/Time</th><th>IP</th><th>Status</th></tr></thead>
        <tbody>
        @php
        $logs = [
            ['Sarah Ahmed','Update','Pricing','Admin updated Claude pricing','Aug 5, 2:14 PM','192.168.•.•','Success'],
            ['Imran Khan','Create','AI Tools','Added new tool "Lumen AI"','Aug 5, 1:02 PM','10.0.•.•','Success'],
            ['Ayesha Raza','Publish','News','Published article "GPT-5.2 Turbo Explained"','Aug 5, 9:00 AM','172.16.•.•','Success'],
            ['System','Verify','News','Auto-verified 14 news items','Aug 5, 8:00 AM','—','Success'],
            ['j.reviewer_92','Delete','Reviews','Attempted to delete a published review','Aug 4, 11:40 PM','203.0.•.•','Failed'],
            ['Sarah Ahmed','Update','Roles','Changed Imran Khan\'s role to Editor','Aug 4, 6:20 PM','192.168.•.•','Success'],
        ];
        @endphp
        @foreach($logs as $l)
        <tr>
            <td><div class="row-media"><div class="thumb">{{ substr($l[0],0,2) }}</div>{{ $l[0] }}</div></td>
            <td><span class="badge badge-neutral">{{ $l[1] }}</span></td>
            <td class="text-sub">{{ $l[2] }}</td>
            <td class="text-sub">{{ $l[3] }}</td>
            <td class="cell-sub">{{ $l[4] }}</td>
            <td class="mono cell-sub">{{ $l[5] }}</td>
            <td><x-status-badge :status="$l[6]" :type="$l[6]==='Success' ? 'pos' : 'neg'" /></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
