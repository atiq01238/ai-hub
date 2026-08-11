@extends('layouts.admin')
@section('title', 'Notification Rules')

@section('content')

<x-page-header title="System Notification &amp; Alert Rules" subtitle="Control which real events trigger a notification" :breadcrumb="['System', 'Notification Rules']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Notify When</th><th>Channel</th><th>Enabled</th></tr></thead>
        <tbody>
        @foreach ($rules as $rule)
        <tr>
            <td><b>{{ $rule->label }}</b></td>
            <td class="text-sub">In-app</td>
            <td>
                <form action="{{ route('admin.system.notification-rules.toggle', $rule->id) }}" method="POST">
                    @csrf
                    <button type="submit" style="background:none; border:none; cursor:pointer; padding:0;">
                        <div class="switch {{ $rule->enabled ? 'is-on' : '' }}"><i></i></div>
                    </button>
                </form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>

<p class="text-sub" style="font-size:12px; margin-top:16px;">
    Only "In-app" channel exists right now — Email/SMS delivery isn't wired up yet (that would
    need your Email integration actually configured, which the Integrations page can already
    tell you the status of).
</p>
@endsection
