@extends('layouts.admin')
@section('title','Two-Factor Authentication')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/administration.css') }}">@endpush
@section('content')
<div class="ad-page ad-2fa-page">
<x-page-header title="Two-Factor Authentication" subtitle="Protect your administrator account with a time-based authenticator code." :breadcrumb="['System','Security Center','2FA']">
<x-slot:actions><a href="{{ route('admin.system.security') }}" class="btn btn-secondary"><i data-lucide="arrow-left"></i>Security Center</a></x-slot:actions>
</x-page-header>
@if(session('status'))<div class="alert alert-success ad-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
<div class="ad-2fa-layout">
<main class="card ad-2fa-card {{ $user->two_factor_enabled?'is-enabled':'' }}">
<div class="ad-2fa-card__state"><span><i data-lucide="{{ $user->two_factor_enabled?'shield-check':'shield-off' }}"></i></span><div><span class="ad-eyebrow">Account Protection</span><h2>2FA is {{ $user->two_factor_enabled?'ON':'OFF' }}</h2><p>{{ $user->two_factor_enabled?'A valid authenticator code is required after password authentication.':'Enable a TOTP authenticator app to add a second login factor.' }}</p></div></div>
@if($user->two_factor_enabled)
<div class="ad-2fa-success"><i data-lucide="badge-check"></i><div><strong>Authenticator protection active</strong><p>Your secret and recovery-code hashes are stored on your account. Recovery codes are shown only at initial setup.</p></div></div>
<form action="{{ route('admin.system.2fa.disable') }}" method="POST" class="ad-2fa-disable" onsubmit="return confirm('Disable two-factor authentication for this account?')">@csrf<label><span>Confirm current password</span><input class="input" type="password" name="password" autocomplete="current-password" required></label>@error('password')<small class="ad-field-error">{{ $message }}</small>@enderror<button class="btn btn-danger" type="submit"><i data-lucide="shield-off"></i>Disable 2FA</button></form>
@else
<form action="{{ route('admin.system.2fa.setup') }}" method="POST">@csrf<button class="btn btn-primary"><i data-lucide="shield-plus"></i>Start Secure Setup</button></form>
@endif
</main>
<aside class="card ad-2fa-guide"><span class="ad-eyebrow">How It Works</span><ol><li><span>1</span><div><strong>Generate secret</strong><small>A temporary TOTP secret is kept in the session.</small></div></li><li><span>2</span><div><strong>Scan & verify</strong><small>Setup is not committed until a valid 6-digit code succeeds.</small></div></li><li><span>3</span><div><strong>Save recovery codes</strong><small>Eight one-time codes are shown once and stored only as hashes.</small></div></li></ol></aside>
</div>
</div>
@endsection
