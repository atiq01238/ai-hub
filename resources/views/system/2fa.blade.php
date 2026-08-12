@extends('layouts.admin')
@section('title', 'Two-Factor Authentication')

@section('content')

<x-page-header title="Two-Factor Authentication" subtitle="Extra protection for your own admin account" :breadcrumb="['System', '2FA']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="card card-pad" style="max-width:520px;">
    @if ($user->two_factor_enabled)
        <div class="flex items-center gap-12" style="margin-bottom:16px;">
            <div class="kpi-icon" style="color:var(--pos);"><i data-lucide="shield-check"></i></div>
            <div>
                <b>2FA is ON</b>
                <div class="cell-sub">Your account requires a code from your authenticator app to log in.</div>
            </div>
        </div>

        <form action="{{ route('admin.system.2fa.disable') }}" method="POST">
            @csrf
            <div class="form-field" style="margin-bottom:12px;">
                <label>Enter your password to disable</label>
                <input class="input" type="password" name="password" required>
            </div>
            @error('password')<div class="text-sub" style="color:var(--neg); margin-bottom:10px;">{{ $message }}</div>@enderror
            <button type="submit" class="btn btn-danger btn-sm">Disable 2FA</button>
        </form>
    @else
        <div class="flex items-center gap-12" style="margin-bottom:16px;">
            <div class="kpi-icon" style="color:var(--text-lo);"><i data-lucide="shield-off"></i></div>
            <div>
                <b>2FA is OFF</b>
                <div class="cell-sub">Add an authenticator app (Google Authenticator, Authy, etc.) for extra login security.</div>
            </div>
        </div>

        <form action="{{ route('admin.system.2fa.setup') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="shield-plus"></i> Enable 2FA</button>
        </form>
    @endif
</div>
@endsection
