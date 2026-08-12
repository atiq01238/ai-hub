<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verify Your Identity</title>
</head>
<body style="font-family: sans-serif; max-width: 380px; margin: 60px auto;">

    <h1>Two-Factor Verification</h1>
    <p>Enter the 6-digit code from your authenticator app.</p>

    @if ($errors->any())
        <p style="color: red;">{{ $errors->first() }}</p>
    @endif

    <form action="{{ route('login.2fa.verify') }}" method="POST">
        @csrf
        <label for="code">Authenticator code</label><br>
        <input type="text" id="code" name="code" maxlength="6" inputmode="numeric" autofocus style="font-size:20px; letter-spacing:4px; width:100%; padding:8px;"><br><br>
        <button type="submit">Verify</button>
    </form>

    <hr style="margin:24px 0;">

    <details>
        <summary style="cursor:pointer;">Use a recovery code instead</summary>
        <form action="{{ route('login.2fa.verify') }}" method="POST" style="margin-top:12px;">
            @csrf
            <label for="recovery_code">Recovery code</label><br>
            <input type="text" id="recovery_code" name="recovery_code" style="width:100%; padding:8px;"><br><br>
            <button type="submit">Use Recovery Code</button>
        </form>
    </details>

</body>
</html>
