<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="color-scheme" content="dark">
    <title>Reset Password — AI Hub</title>
    <link rel="stylesheet" href="{{ asset('css/frontend/auth-unified.css') }}">
</head>
<body>
<div class="auth-shell">
    <section class="auth-hero is-login" aria-label="AI Hub account security">
        <div class="auth-grid" aria-hidden="true"></div>
        <a class="auth-brand" href="{{ route('home') }}" aria-label="AI Hub home">
            <span class="auth-logo" aria-hidden="true">
                <svg viewBox="0 0 32 32" fill="none"><path d="M16 2.8 27.2 9.2v13.6L16 29.2 4.8 22.8V9.2L16 2.8Z" stroke="url(#g1)" stroke-width="2.1"/><path d="m16 8 6.6 3.8v8.4L16 24l-6.6-3.8v-8.4L16 8Z" stroke="url(#g2)" stroke-width="2.1"/><circle cx="16" cy="16" r="2.4" fill="#86A8FF"/><defs><linearGradient id="g1" x1="5" y1="4" x2="27" y2="27"><stop stop-color="#24B6FF"/><stop offset="1" stop-color="#B24EFF"/></linearGradient><linearGradient id="g2" x1="10" y1="9" x2="22" y2="23"><stop stop-color="#6BE7FF"/><stop offset="1" stop-color="#7955FF"/></linearGradient></defs></svg>
            </span>
            <span class="auth-brand-name">AI Hub</span>
        </a>
        <div class="auth-hero-copy">
            <span class="auth-kicker">Secure account recovery</span>
            <h1>Back to your AI.<br><span class="auth-gradient-text">Securely.</span></h1>
            <p>Reset access with a time-limited email link. Your saved tools, comparisons, follows, and AI Hub preferences stay with your account.</p>
        </div>
        <div class="auth-feature-row" aria-hidden="true">
            <div class="auth-feature"><span class="auth-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span><b>Secure Link</b><span>Time-limited token</span></div>
            <div class="auth-feature"><span class="auth-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3 4 7v5c0 5 3.5 8 8 9 4.5-1 8-4 8-9V7l-8-4Z"/><path d="m9 12 2 2 4-4"/></svg></span><b>Protected</b><span>Secure reset flow</span></div>
            <div class="auth-feature"><span class="auth-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 4h16v16H4z"/><path d="m4 7 8 6 8-6"/></svg></span><b>Email</b><span>Delivered privately</span></div>
            <div class="auth-feature"><span class="auth-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/></svg></span><b>60 Minutes</b><span>Reset link expiry</span></div>
        </div>
    </section>
    <main class="auth-panel">
        <a class="auth-home-link" href="{{ route('home') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/></svg>Back to AI Hub</a>
        <section class="auth-card auth-recovery-card" aria-labelledby="reset-title">
            <div class="auth-recovery-badge" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3a9 9 0 1 0 8.5 6"/><path d="M20 3v6h-6"/><path d="M12 8v4l3 2"/></svg></div>
            <h2 id="reset-title">Create a new password</h2>
            <p class="auth-subtitle">Choose a new password for your AI Hub account.</p>

            @if ($errors->any())
                <div class="auth-alert error">The reset link or information could not be verified. Please check the highlighted fields.</div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="auth-field">
                    <label for="email">Email</label>
                    <div class="auth-input-wrap @error('email') has-error @enderror">
                        <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" placeholder="you@example.com" autocomplete="email" required>
                    </div>
                    @error('email')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="auth-field">
                    <label for="password">New password</label>
                    <div class="auth-input-wrap @error('password') has-error @enderror">
                        <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <input id="password" name="password" type="password" placeholder="Minimum 8 characters" autocomplete="new-password" minlength="8" required autofocus>
                        <button type="button" class="auth-password-toggle" data-password-toggle="password" aria-label="Show password"></button>
                    </div>
                    @error('password')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="auth-field">
                    <label for="password_confirmation">Confirm new password</label>
                    <div class="auth-input-wrap">
                        <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Repeat your new password" autocomplete="new-password" minlength="8" required>
                        <button type="button" class="auth-password-toggle" data-password-toggle="password_confirmation" aria-label="Show password"></button>
                    </div>
                </div>
                <button class="auth-submit" type="submit"><span>Reset Password</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
            </form>
            <p class="auth-back-row"><a href="{{ route('login') }}">Back to Sign In</a></p>
        </section>
    </main>
</div>
<script src="{{ asset('js/frontend/auth-unified.js') }}" defer></script>
</body>
</html>
