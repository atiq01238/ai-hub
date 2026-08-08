@extends('layouts.admin')
@section('title', 'Two-Factor Authentication')

@section('content')

<x-page-header title="Two-Factor Authentication" subtitle="Enabled via Authenticator App" :breadcrumb="['System', 'Security Center', '2FA']" />

<div class="grid-12">
    <div class="col-6 card card-pad" style="text-align:center;">
        <div class="section-title" style="text-align:left;">Setup — Scan QR Code</div>
        <div style="width:180px; height:180px; margin:16px auto; background:var(--surface-2); border-radius:var(--radius-md); display:flex; align-items:center; justify-content:center; border:1px dashed var(--border);">
            <i data-lucide="qr-code" style="width:64px;height:64px; color:var(--text-lo);"></i>
        </div>
        <div class="text-sub" style="font-size:12.5px; margin-bottom:16px;">Scan with Google Authenticator, Authy, or 1Password.</div>
        <div class="form-field" style="max-width:220px; margin:0 auto 14px;">
            <label>Verification Code</label>
            <input class="input" placeholder="000000" style="text-align:center; letter-spacing:.3em; font-family:var(--font-mono);">
        </div>
        <button class="btn btn-primary" style="width:100%; max-width:220px; justify-content:center;">Verify &amp; Enable</button>
    </div>

    <div class="col-6">
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="flex items-center justify-between">
                <div>
                    <b style="font-size:14px;">2FA Status</b>
                    <div class="cell-sub">Currently protecting your account</div>
                </div>
                <span class="badge badge-pos">Enabled</span>
            </div>
        </div>

        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="section-title">Backup Codes</div>
            <div class="text-sub" style="font-size:12.5px; margin-bottom:12px;">Store these somewhere safe. Each code works once.</div>
            <div class="grid-2 mono" style="gap:8px; font-size:12.5px;">
                @foreach(['8X2K-91QF','3ML0-QW7Z','P29D-XX41','7VNC-K02L','B3XZ-19MQ','MK41-2Q0P'] as $code)
                    <div class="card-pad" style="padding:8px 10px; background:var(--surface-2); border-radius:8px;">{{ $code }}</div>
                @endforeach
            </div>
            <button class="btn btn-secondary btn-sm" style="margin-top:14px;"><i data-lucide="refresh-cw"></i> Regenerate Backup Codes</button>
        </div>

        <div class="card card-pad">
            <div class="section-title">Recovery &amp; Disable</div>
            <div class="flex items-center justify-between" style="margin-bottom:12px;">
                <span class="text-sub" style="font-size:13px;">Recovery email</span>
                <span style="font-size:13px;">s••••@aihub.io</span>
            </div>
            <button class="btn btn-danger btn-sm"><i data-lucide="shield-off"></i> Disable 2FA</button>
        </div>
    </div>
</div>
@endsection
