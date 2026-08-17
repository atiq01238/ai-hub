@extends('layouts.admin')
@section('title','Two-Factor Recovery Codes')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/administration.css') }}">@endpush
@section('content')
<div class="ad-page ad-2fa-page">
<x-page-header title="Save Your Recovery Codes" subtitle="These one-time codes are shown only now. Store them somewhere secure before continuing." :breadcrumb="['System','Security Center','2FA','Recovery Codes']" />
<section class="ad-recovery-warning"><i data-lucide="triangle-alert"></i><div><strong>One-time display</strong><p>The database stores only password-style hashes. These exact recovery codes cannot be displayed again later.</p></div></section>
<div class="ad-recovery-layout">
<section class="card ad-recovery-card"><span class="ad-eyebrow">Emergency Login Codes</span><h2>8 one-time recovery codes</h2><div class="ad-recovery-grid" id="recoveryCodes">@foreach($recoveryCodes as $code)<code>{{ $code }}</code>@endforeach</div><button type="button" class="btn btn-secondary ad-full" onclick="copyRecoveryCodes()"><i data-lucide="copy"></i>Copy All Codes</button></section>
<aside class="card ad-2fa-guide"><span class="ad-eyebrow">Storage Guidance</span><ol><li><span>1</span><div><strong>Store offline or in a password manager</strong><small>Do not keep the only copy in the same device as your authenticator.</small></div></li><li><span>2</span><div><strong>Each code works once</strong><small>A successfully used recovery code is burned and removed.</small></div></li><li><span>3</span><div><strong>Continue only after saving</strong><small>You will not be able to reveal these codes again.</small></div></li></ol><a href="{{ route('admin.system.2fa') }}" class="btn btn-primary ad-full"><i data-lucide="check"></i>I Saved My Codes</a></aside>
</div>
</div>
@endsection
@push('scripts')
<script>
function copyRecoveryCodes(){const codes=[...document.querySelectorAll('#recoveryCodes code')].map(x=>x.textContent.trim()).join('\n');navigator.clipboard?.writeText(codes)}
</script>
@endpush
