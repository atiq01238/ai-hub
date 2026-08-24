<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset(config('brand.assets.favicon_32')) }}">
<title>Two-Factor Verification · {{ config('app.name','AI Orbit') }}</title>
<link rel="stylesheet" href="{{ asset('css/pages/administration.css') }}">
</head>
<body class="ad-auth-body">
<main class="ad-auth-shell">
<section class="ad-auth-brand"><span class="ad-auth-brand__mark"><img src="{{ asset(config('brand.assets.icon')) }}" alt="" aria-hidden="true"></span><span>{{ config('app.name','AI Orbit') }}</span></section>
<section class="ad-auth-card">
<div class="ad-auth-icon">⌁</div>
<span class="ad-eyebrow">Secure Login</span>
<h1>Verify your identity</h1>
<p>Enter the current 6-digit code from your authenticator app to finish signing in.</p>
@if($errors->any())<div class="ad-auth-error">{{ $errors->first() }}</div>@endif
<form action="{{ route('login.2fa.verify') }}" method="POST" class="ad-auth-form">@csrf
<label><span>Authenticator code</span><input class="ad-auth-input ad-auth-code" type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" autofocus placeholder="000000"></label>
<button class="ad-auth-button" type="submit">Verify & Continue</button>
</form>
<div class="ad-auth-divider"><span>or</span></div>
<details class="ad-auth-recovery"><summary>Use a recovery code instead</summary><form action="{{ route('login.2fa.verify') }}" method="POST" class="ad-auth-form">@csrf<label><span>One-time recovery code</span><input class="ad-auth-input" type="text" name="recovery_code" autocomplete="off" placeholder="XXXX-XXXX"></label><button class="ad-auth-button is-secondary" type="submit">Use Recovery Code</button></form></details>
</section>
<p class="ad-auth-foot">Second-factor verification protects administrator access even when a password is compromised.</p>
</main>
</body>
</html>
