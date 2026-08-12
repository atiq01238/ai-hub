<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class LoginController extends Controller
{
    // Show Login Page
    public function index()
    {
        return view('auth.login');
    }

    // Login User
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->has('remember'))) {

            $user = Auth::user();

            if ($user->two_factor_enabled) {
                // Correct password, but don't fully log them in yet — log
                // straight back out and hold their ID in the session until
                // they also pass the 2FA challenge below.
                Auth::logout();

                $request->session()->put('2fa.login_user_id', $user->id);
                $request->session()->put('2fa.remember', $request->has('remember'));

                return redirect()->route('login.2fa');
            }

            $request->session()->regenerate();

            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }

    // Show the "enter your 6-digit code" screen — only reachable if they
    // already passed the password check above.
    public function twoFactorChallenge(Request $request)
    {
        if (! $request->session()->has('2fa.login_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.2fa-challenge');
    }

    // Verify the code (or a one-time recovery code) and complete login.
    public function verifyTwoFactor(Request $request)
    {
        $userId = $request->session()->get('2fa.login_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = User::findOrFail($userId);

        $verified = false;

        if (ctype_digit($data['code']) && strlen($data['code']) === 6) {
            $verified = (new Google2FA())->verifyKey($user->two_factor_secret, $data['code']);
        } else {
            // Not a 6-digit code — check it against their recovery codes instead.
            $codes = $user->two_factor_recovery_codes ?? [];
            foreach ($codes as $i => $hashedCode) {
                if (Hash::check(strtoupper($data['code']), $hashedCode)) {
                    $verified = true;
                    unset($codes[$i]); // recovery codes are single-use
                    $user->two_factor_recovery_codes = array_values($codes);
                    $user->save();
                    break;
                }
            }
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'That code is incorrect.']);
        }

        $remember = $request->session()->get('2fa.remember', false);
        $request->session()->forget(['2fa.login_user_id', '2fa.remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
