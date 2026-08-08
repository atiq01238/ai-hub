@extends('layouts.admin')
@section('title', 'Notification Rules')

@section('content')

<x-page-header title="System Notification &amp; Alert Rules" subtitle="Control what triggers a notification, and who receives it" :breadcrumb="['System', 'Notification Rules']">
    <x-slot:actions><button class="btn btn-primary btn-sm"><i data-lucide="plus"></i> New Rule</button></x-slot:actions>
</x-page-header>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Notify When</th><th>Channel</th><th>Priority</th><th>Recipient Role</th><th>Enabled</th></tr></thead>
        <tbody>
        @php
        $rules = [
            ['New breaking AI news arrives','In-app + Email','High','All Admins',true],
            ['Price changes','In-app','Medium','Editor, Admin',true],
            ['API fails','In-app + SMS','Critical','Super Admin',true],
            ['Source stops sending data','In-app','Medium','Admin',true],
            ['Duplicate news detected','In-app','Low','Moderator',false],
            ['Security issue detected','In-app + Email + SMS','Critical','Super Admin',true],
            ['System health becomes critical','In-app + Email + SMS','Critical','Super Admin, Admin',true],
            ['Benchmark becomes outdated','In-app','Low','Researcher',true],
            ['Tool link breaks','In-app','Medium','Editor',false],
        ];
        @endphp
        @foreach($rules as $r)
        <tr>
            <td><b>{{ $r[0] }}</b></td>
            <td class="text-sub">{{ $r[1] }}</td>
            <td><span class="badge badge-{{ $r[2]==='Critical'?'neg':($r[2]==='High'?'warn':($r[2]==='Medium'?'info':'neutral')) }}">{{ $r[2] }}</span></td>
            <td class="text-sub">{{ $r[3] }}</td>
            <td><div class="switch {{ $r[4] ? 'is-on' : '' }}"><i></i></div></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
