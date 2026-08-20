@extends('frontend.layouts.app')
@section('title','Settings & Security — My AI Hub')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/account.css') }}">@endpush
@section('content')
<section class="account-page"><div class="account-shell">
@include('frontend.account._sidebar')
<div class="account-main">
    <header class="account-subhead"><div><span class="account-kicker"><i data-lucide="settings"></i> ACCOUNT</span><h1>Settings & security</h1><p>Manage your identity and account security without leaving AI Hub.</p></div></header>

    @if(session('status'))<div class="account-success"><i data-lucide="circle-check"></i>{{ session('status') }}</div>@endif
    @if($errors->any())<div class="account-error"><i data-lucide="triangle-alert"></i><div>@foreach($errors->all() as $error)<span>{{ $error }}</span>@endforeach</div></div>@endif

    <div class="settings-grid">
        <section class="account-panel settings-card">
            <div class="settings-title"><span><i data-lucide="user-round"></i></span><div><h2>Profile information</h2><p>Your name is shown across your private AI Hub account.</p></div></div>
            <form method="POST" action="{{ route('account.profile.update') }}">
                @csrf @method('PATCH')
                <label><span>Name</span><input name="name" value="{{ old('name',$user->name) }}" maxlength="100" required></label>
                <label><span>Email</span><input value="{{ $user->email }}" disabled><small>Email changes are intentionally locked until verification workflow is enabled.</small></label>
                <button type="submit"><i data-lucide="save"></i>Save profile</button>
            </form>
        </section>

        <section class="account-panel settings-card">
            <div class="settings-title"><span><i data-lucide="lock-keyhole"></i></span><div><h2>Change password</h2><p>Use a strong password that you do not reuse elsewhere.</p></div></div>
            <form method="POST" action="{{ route('account.password.update') }}">
                @csrf @method('PATCH')
                <label><span>Current password</span><input type="password" name="current_password" autocomplete="current-password" required></label>
                <label><span>New password</span><input type="password" name="password" autocomplete="new-password" required></label>
                <label><span>Confirm new password</span><input type="password" name="password_confirmation" autocomplete="new-password" required></label>
                <button type="submit"><i data-lucide="shield-check"></i>Update password</button>
            </form>
        </section>

        <section class="account-panel security-card">
            <div class="settings-title"><span><i data-lucide="shield-check"></i></span><div><h2>Security overview</h2><p>Key account protections currently attached to your profile.</p></div></div>
            <div class="security-rows">
                <div><span><i data-lucide="mail-check"></i><b>Email verification</b></span><em class="{{ $user->email_verified_at ? 'on':'off' }}">{{ $user->email_verified_at ? 'Verified':'Not verified' }}</em></div>
                <div><span><i data-lucide="smartphone"></i><b>Two-factor authentication</b></span><em class="{{ $user->two_factor_enabled ? 'on':'off' }}">{{ $user->two_factor_enabled ? 'Enabled':'Disabled' }}</em></div>
                <div><span><i data-lucide="shield"></i><b>Account status</b></span><em class="{{ ($user->status ?? 'active') === 'active' ? 'on':'off' }}">{{ ucfirst($user->status ?? 'active') }}</em></div>
                <div><span><i data-lucide="clock"></i><b>Last sign in</b></span><em>{{ $user->last_login_at?->diffForHumans() ?? 'Not recorded' }}</em></div>
            </div>
        </section>
    </div>
</div></div></section>
@endsection
