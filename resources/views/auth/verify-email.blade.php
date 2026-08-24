<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset(config('brand.assets.favicon_32')) }}"><meta name="color-scheme" content="dark"><title>Verify Email — AI Orbit</title><link rel="stylesheet" href="{{ asset('css/frontend/auth-unified.css') }}"></head>
<body><div class="auth-shell">
<section class="auth-hero is-login" aria-label="AI Orbit email verification"><div class="auth-grid" aria-hidden="true"></div>
<a class="auth-brand" href="{{ route('home') }}"><span class="auth-logo auth-logo-orbit" aria-hidden="true"><img src="{{ asset(config('brand.assets.icon')) }}" alt=""></span><span class="auth-brand-name">AI Orbit</span></a>
<div class="auth-hero-copy"><span class="auth-kicker">One secure step</span><h1>Verify your email.<br><span class="auth-gradient-text">Unlock your AI Orbit.</span></h1><p>Verification protects your account and activates saved items, community actions, personalized discovery, and intelligence email preferences.</p></div>
<div class="auth-feature-row" aria-hidden="true"><div class="auth-feature"><span class="auth-feature-icon">✓</span><b>Secure</b><span>Signed verification</span></div><div class="auth-feature"><span class="auth-feature-icon">✉</span><b>Email</b><span>Private confirmation</span></div><div class="auth-feature"><span class="auth-feature-icon">AI</span><b>Personalized</b><span>Your own AI Orbit</span></div><div class="auth-feature"><span class="auth-feature-icon">↻</span><b>Resend</b><span>Fresh link anytime</span></div></div></section>
<main class="auth-panel"><a class="auth-home-link" href="{{ route('home') }}">← Back to AI Orbit</a><section class="auth-card auth-recovery-card"><div class="auth-recovery-badge">@</div><h2>Check your inbox</h2><p class="auth-subtitle">We sent a verification link to <strong>{{ auth()->user()->email }}</strong>.</p>
@if(session('status')==='verification-link-sent')<div class="auth-alert success">A fresh verification link has been sent.</div>@endif
<div class="auth-recovery-note">Open the email and click “Verify email address”. If you do not see it, check Spam/Junk and then resend.</div>
<form method="POST" action="{{ route('verification.send') }}">@csrf<button class="auth-submit" type="submit"><span>Resend Verification Email</span><span>→</span></button></form>
<form method="POST" action="{{ route('logout') }}" style="margin-top:12px">@csrf<button class="auth-social" type="submit" style="width:100%;justify-content:center">Sign out</button></form>
</section></main></div></body></html>