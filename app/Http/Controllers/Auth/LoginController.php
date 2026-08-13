<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $success = Auth::attempt($credentials, $request->has('remember'));

        // Log every attempt — successful or not — so the Security Center
        // has something real to show.
        LoginAttempt::create([
            'email'       => $credentials['email'],
            'user_id'     => $success ? Auth::id() : null,
            'ip_address'  => $request->ip(),
            'user_agent'  => substr((string) $request->userAgent(), 0, 255),
            'successful'  => $success,
        ]);

        if ($success) {

            $user = Auth::user();

            // Password is correct — but if this account has 2FA turned on,
            // don't fully log them in yet. Log out immediately, remember
            // WHO they are (not that they're authenticated), and send them
            // to the code-entry screen instead.
            if ($user->two_factor_enabled) {
                Auth::logout();

                $request->session()->put('2fa_user_id', $user->id);
                $request->session()->put('2fa_remember', $request->has('remember'));

                return redirect()->route('login.2fa');
            }

            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
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
