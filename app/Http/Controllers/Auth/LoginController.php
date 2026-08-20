<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Services\Frontend\SavedItemService;
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

        $candidate = User::where('email', $credentials['email'])->first();
        $candidate?->restoreIfSuspensionExpired();
        $isSuspended = $candidate && $candidate->status !== 'active';
        $success = ! $isSuspended && Auth::attempt($credentials, $request->boolean('remember'));

        // Log every attempt — successful or not — so the Security Center
        // has something real to show.
        LoginAttempt::create([
            'email'       => $credentials['email'],
            'user_id'     => $success ? Auth::id() : $candidate?->id,
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

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            $completedAction = app(SavedItemService::class)->consumePending($request, $user);
            if ($completedAction) {
                $request->session()->flash('status', $completedAction['message']);
            }

            $destination = $user->role === 'admin'
                ? route('admin.dashboard')
                : route('home');

            return redirect()->intended($destination);
        }

        return back()->withErrors([
            'email' => $isSuspended
                ? 'This account is suspended. Contact support if you believe this is a mistake.'
                : 'Invalid email or password.',
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