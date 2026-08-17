@extends('layouts.admin')
@section('title','Set Up Two-Factor Authentication')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/administration.css') }}">@endpush
@section('content')
<div class="ad-page ad-2fa-page">
<x-page-header title="Set Up Two-Factor Authentication" subtitle="Scan the TOTP secret, then prove setup works before it is saved to your account." :breadcrumb="['System','Security Center','2FA','Setup']">
<x-slot:actions><a href="{{ route('admin.system.2fa') }}" class="btn btn-secondary"><i data-lucide="x"></i>Cancel Setup</a></x-slot:actions>
</x-page-header>
@if($errors->any())<div class="alert alert-danger ad-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif
<div class="ad-setup-layout">
<section class="card ad-qr-card"><span class="ad-eyebrow">Step 1 · Authenticator App</span><h2>Scan this QR code</h2><p>Use Google Authenticator, Microsoft Authenticator, Authy or another TOTP-compatible app.</p><div id="twoFactorQr" class="ad-qr"></div><div class="ad-secret"><span>Manual setup key</span><code>{{ $secret }}</code><button type="button" class="icon-btn" onclick="navigator.clipboard?.writeText('{{ $secret }}')" title="Copy secret"><i data-lucide="copy"></i></button></div></section>
<section class="card ad-confirm-card"><span class="ad-eyebrow">Step 2 · Verification</span><div class="ad-confirm-card__icon"><i data-lucide="smartphone"></i></div><h2>Enter the current 6-digit code</h2><p>2FA remains disabled until this test succeeds.</p><form action="{{ route('admin.system.2fa.confirm') }}" method="POST">@csrf<label><span>Authenticator Code</span><input class="input ad-code-input" type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" autofocus required placeholder="000000"></label><button class="btn btn-primary" type="submit"><i data-lucide="shield-check"></i>Verify & Enable 2FA</button></form><div class="ad-setup-safety"><i data-lucide="lock-keyhole"></i><span>The pending secret exists in the session only until successful verification.</span></div></section>
</div>
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){const el=document.getElementById('twoFactorQr');if(el&&typeof QRCode!=='undefined'){new QRCode(el,{text:@json($qrCodeUrl),width:200,height:200,colorDark:'#111827',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.M});}})
</script>
@endpush
