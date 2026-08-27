<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset(config('brand.assets.favicon_32')) }}">
    <meta name="color-scheme" content="dark">
    <title>Forgot Password — AI Orbit</title>
    <link rel="stylesheet" href="{{ asset('css/frontend/auth-unified.css') }}">
</head>
<body>
<div class="auth-shell">
    <section class="auth-hero is-login" aria-label="AI Orbit account security">
        <div class="auth-grid" aria-hidden="true"></div>
        <a class="auth-brand" href="{{ route('home') }}" aria-label="AI Orbit home">
            <span class="auth-logo auth-logo-orbit" aria-hidden="true"><img src="{{ asset(config('brand.assets.icon')) }}" alt=""></span>
            <span class="auth-brand-name">AI Orbit</span>
        </a>
        <div class="auth-hero-copy">
            <span class="auth-kicker">Secure account recovery</span>
            <h1>Back to your AI.<br><span class="auth-gradient-text">Securely.</span></h1>
            <p>Reset access with a time-limited email link. Your saved tools, comparisons, follows, and AI Orbit preferences stay with your account.</p>
        </div>
        <div class="auth-feature-row" aria-hidden="true">
            <div class="auth-feature"><span class="auth-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span><b>Secure Link</b><span>Time-limited token</span></div>
            <div class="auth-feature"><span class="auth-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3 4 7v5c0 5 3.5 8 8 9 4.5-1 8-4 8-9V7l-8-4Z"/><path d="m9 12 2 2 4-4"/></svg></span><b>Protected</b><span>Secure reset flow</span></div>
            <div class="auth-feature"><span class="auth-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 4h16v16H4z"/><path d="m4 7 8 6 8-6"/></svg></span><b>Email</b><span>Delivered privately</span></div>
            <div class="auth-feature"><span class="auth-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/></svg></span><b>60 Minutes</b><span>Reset link expiry</span></div>
        </div>
    </section>
    <main class="auth-panel">
        <a class="auth-home-link" href="{{ route('home') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/></svg>Back to AI Orbit</a>
        <section class="auth-card auth-recovery-card" aria-labelledby="forgot-title">
            <div class="auth-recovery-badge" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 17v.01"/><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></div>
            <h2 id="forgot-title">Forgot your password?</h2>
            <p class="auth-subtitle">Enter your account email and we’ll send a secure password reset link.</p>

            @if (session('status'))
                <div class="auth-alert success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="auth-alert error">Please check the email address and try again.</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf
                <div class="auth-field">
                    <label for="email">Email</label>
                    <div class="auth-input-wrap @error('email') has-error @enderror">
                        <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required autofocus>
                    </div>
                    @error('email')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="auth-recovery-note">For privacy, AI Orbit shows the same confirmation whether or not the email is registered.</div>
                <button class="auth-submit" type="submit"><span>Send Reset Link</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
            </form>
            <p class="auth-back-row">Remembered it? <a href="{{ route('login') }}">Back to Sign In</a></p>
        </section>
    </main>
</div>
<script src="{{ asset('js/auth-unified.js') }}" defer></script>
</body>
</html>
