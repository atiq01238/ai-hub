<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="color-scheme" content="dark">
    <title>Sign In — AI Hub</title>
    <link rel="stylesheet" href="{{ asset('css/frontend/auth-unified.css') }}">
</head>
<body>
<div class="auth-shell">
    <section class="auth-hero is-login" aria-label="AI Hub discovery platform">
        <div class="auth-grid" aria-hidden="true"></div>

        <a class="auth-brand" href="{{ route('home') }}" aria-label="AI Hub home">
            <span class="auth-logo" aria-hidden="true">
                <svg viewBox="0 0 32 32" fill="none">
                    <path d="M16 2.8 27.2 9.2v13.6L16 29.2 4.8 22.8V9.2L16 2.8Z" stroke="url(#g1)" stroke-width="2.1"/>
                    <path d="m16 8 6.6 3.8v8.4L16 24l-6.6-3.8v-8.4L16 8Z" stroke="url(#g2)" stroke-width="2.1"/>
                    <circle cx="16" cy="16" r="2.4" fill="#86A8FF"/>
                    <defs><linearGradient id="g1" x1="5" y1="4" x2="27" y2="27"><stop stop-color="#24B6FF"/><stop offset="1" stop-color="#B24EFF"/></linearGradient><linearGradient id="g2" x1="10" y1="9" x2="22" y2="23"><stop stop-color="#6BE7FF"/><stop offset="1" stop-color="#7955FF"/></linearGradient></defs>
                </svg>
            </span>
            <span class="auth-brand-name">AI Hub</span>
        </a>

        <div class="auth-hero-copy">
            <span class="auth-kicker">Your gateway to the world of AI</span>
            <h1>Explore. Compare.<br><span class="auth-gradient-text">Stay Ahead.</span></h1>
            <p>Discover AI tools, models, benchmarks, news, and comparisons in one place. Make smarter decisions with trusted AI intelligence.</p>
        </div>

        <div class="auth-feature-row" aria-hidden="true">
            <div class="auth-feature">
                <span class="auth-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m12 2 8 4.5v11L12 22l-8-4.5v-11L12 2Z"/><path d="m4.4 6.7 7.6 4.4 7.6-4.4M12 11v11"/></svg></span>
                <b>AI Tools</b><span>Explore top tools</span>
            </div>
            <div class="auth-feature">
                <span class="auth-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M9.5 4.5A3.5 3.5 0 0 0 6 8v1a3 3 0 0 0-1 5.8V16a4 4 0 0 0 4 4h1V4.5h-.5ZM14.5 4.5A3.5 3.5 0 0 1 18 8v1a3 3 0 0 1 1 5.8V16a4 4 0 0 1-4 4h-1V4.5h.5Z"/></svg></span>
                <b>Models</b><span>Compare capabilities</span>
            </div>
            <div class="auth-feature">
                <span class="auth-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 20V10M10 20V4M16 20v-7M22 20V7"/></svg></span>
                <b>Benchmarks</b><span>Performance insights</span>
            </div>
            <div class="auth-feature">
                <span class="auth-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 3h14v18H5z"/><path d="M8 7h8M8 11h8M8 15h5"/></svg></span>
                <b>AI News</b><span>Stay informed</span>
            </div>
        </div>
    </section>

    <main class="auth-panel">
        <a class="auth-home-link" href="{{ route('home') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/></svg>
            Back to AI Hub
        </a>

        <section class="auth-card" aria-labelledby="login-title">
            <nav class="auth-tabs" aria-label="Authentication">
                <a class="auth-tab active" href="{{ route('login') }}" aria-current="page">Sign In</a>
                <a class="auth-tab" href="{{ route('signup') }}">Sign Up</a>
            </nav>

            <h2 id="login-title">Welcome back</h2>
            <p class="auth-subtitle">Sign in to continue to AI Hub.</p>

            @if (session('success'))
                <div class="auth-alert success">{{ session('success') }}</div>
            @endif
            @if (session('status'))
                <div class="auth-alert success">{{ session('status') }}</div>
            @endif
            @if ($errors->has('social'))
                <div class="auth-alert error">{{ $errors->first('social') }}</div>
            @elseif ($errors->any())
                <div class="auth-alert error">Please check the highlighted information and try again.</div>
            @endif

            <form method="POST" action="{{ route('login.authenticate') }}" novalidate>
                @csrf
                <div class="auth-field">
                    <label for="email">Email</label>
                    <div class="auth-input-wrap @error('email') has-error @enderror">
                        <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required autofocus>
                    </div>
                    @error('email')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <div class="auth-input-wrap @error('password') has-error @enderror">
                        <svg class="auth-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <input id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="auth-password-toggle" data-password-toggle="password" aria-label="Show password"></button>
                    </div>
                    @error('password')<span class="auth-field-error">{{ $message }}</span>@enderror
                </div>

                <div class="auth-form-row">
                    <label class="auth-check"><input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}> <span>Remember me</span></label>
                    <a class="auth-link" href="{{ route('password.request') }}">Forgot password?</a>
                </div>

                <button class="auth-submit" type="submit">
                    <span>Sign In</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>
            </form>

            <div class="auth-divider">or continue with</div>
            <div class="auth-socials" aria-label="Social sign in">
                <a class="auth-social" href="{{ route('social.redirect', ['provider' => 'google', 'origin' => 'login']) }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M21.6 12.23c0-.71-.06-1.4-.18-2.06H12v3.9h5.38a4.6 4.6 0 0 1-2 3.02v2.53h3.24c1.9-1.75 2.98-4.33 2.98-7.39Z"/><path fill="#34A853" d="M12 22c2.7 0 4.97-.9 6.63-2.38l-3.24-2.53c-.9.6-2.05.96-3.39.96-2.61 0-4.82-1.76-5.61-4.13H3.04v2.6A10 10 0 0 0 12 22Z"/><path fill="#FBBC05" d="M6.39 13.92A6 6 0 0 1 6.08 12c0-.67.12-1.32.31-1.92v-2.6H3.04A10 10 0 0 0 2 12c0 1.61.39 3.13 1.04 4.52l3.35-2.6Z"/><path fill="#EA4335" d="M12 5.95c1.47 0 2.79.5 3.83 1.5l2.87-2.87A9.62 9.62 0 0 0 12 2 10 10 0 0 0 3.04 7.48l3.35 2.6C7.18 7.71 9.39 5.95 12 5.95Z"/></svg>
                    Google
                </a>
                <a class="auth-social" href="{{ route('social.redirect', ['provider' => 'apple', 'origin' => 'login']) }}">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.7 12.8c0-2.4 2-3.6 2.1-3.7a4.5 4.5 0 0 0-3.5-1.9c-1.5-.2-2.9.9-3.7.9-.8 0-2-.9-3.3-.9a4.9 4.9 0 0 0-4.1 2.5c-1.8 3-.5 7.6 1.2 10 .9 1.2 1.9 2.6 3.2 2.5 1.3 0 1.8-.8 3.4-.8 1.6 0 2 .8 3.4.8 1.4 0 2.3-1.2 3.1-2.5a10.7 10.7 0 0 0 1.4-2.9 4.2 4.2 0 0 1-2.5-3.9l.1-.1ZM14.3 5.7A4.2 4.2 0 0 0 15.4 2a4.5 4.5 0 0 0-3 1.4 4 4 0 0 0-1.1 3.5 3.7 3.7 0 0 0 3-1.2Z"/></svg>
                    Apple
                </a>
            </div>
            <p class="auth-social-legal">If this is your first visit, continuing with Google or Apple creates your AI Hub account and means you agree to our <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms</a> and <a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy Policy</a>.</p>

            <p class="auth-switch-copy">Don’t have an account? <a href="{{ route('signup') }}">Create one</a></p>
        </section>
    </main>
</div>
<script src="{{ asset('js/frontend/auth-unified.js') }}" defer></script>
</body>
</html>
