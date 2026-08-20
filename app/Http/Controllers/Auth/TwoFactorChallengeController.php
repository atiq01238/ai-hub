<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Frontend\SavedItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request)
    {
        if (! $request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $userId = $request->session()->get('2fa_user_id');
        abort_unless($userId, 400);

        $user = User::findOrFail($userId);

        if ($user->status !== 'active') {
            $request->session()->forget(['2fa_user_id', '2fa_remember']);

            return redirect()->route('login')->withErrors([
                'email' => 'This account is suspended. Contact support if you believe this is a mistake.',
            ]);
        }

        $data = $request->validate([
            'code' => ['nullable', 'digits:6'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $verified = false;

        if (! empty($data['code'])) {
            $verified = (new Google2FA())->verifyKey($user->two_factor_secret, $data['code']);
        } elseif (! empty($data['recovery_code'])) {
            $submitted = strtoupper(trim($data['recovery_code']));
            $storedHashes = array_values($user->two_factor_recovery_codes ?? []);

            foreach ($storedHashes as $index => $hash) {
                if (is_string($hash) && Hash::check($submitted, $hash)) {
                    $verified = true;
                    unset($storedHashes[$index]);
                    $user->two_factor_recovery_codes = array_values($storedHashes);
                    $user->save();
                    break;
                }
            }
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'That code was not valid.']);
        }

        $request->session()->forget('2fa_user_id');

        Auth::login($user, $request->session()->pull('2fa_remember', false));
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
}
