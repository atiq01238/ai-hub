<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $data = $request->validate([
            'code'          => ['nullable', 'digits:6'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $verified = false;

        if (! empty($data['code'])) {
            $verified = (new Google2FA())->verifyKey($user->two_factor_secret, $data['code']);
        } elseif (! empty($data['recovery_code'])) {
            $codes = $user->two_factor_recovery_codes ?? [];
            if (in_array($data['recovery_code'], $codes, true)) {
                $verified = true;
                // Burn the used recovery code so it can't be reused.
                $user->two_factor_recovery_codes = array_values(array_diff($codes, [$data['recovery_code']]));
                $user->save();
            }
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'That code was not valid.']);
        }

        $request->session()->forget('2fa_user_id');

        Auth::login($user, $request->session()->pull('2fa_remember', false));
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }
}
