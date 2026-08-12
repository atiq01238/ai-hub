<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function index()
    {
        return view('system.2fa', ['user' => auth()->user()]);
    }

    /**
     * Generate a new secret, stash it in the SESSION (not the database yet —
     * it only becomes permanent once they prove they scanned it correctly
     * in confirm() below), and show the QR code to scan.
     */
    public function setup(Request $request)
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $request->session()->put('2fa.pending_secret', $secret);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            auth()->user()->email,
            $secret
        );

        return view('system.2fa-setup', [
            'secret'      => $secret,
            'qrCodeUrl'   => $qrCodeUrl, // an otpauth:// URI — rendered into a QR image client-side
        ]);
    }

    /**
     * They enter the 6-digit code from their authenticator app to prove
     * the QR scan actually worked before we turn 2FA on for real.
     */
    public function confirm(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'digits:6']]);

        $secret = $request->session()->get('2fa.pending_secret');
        abort_unless($secret, 400, 'Setup session expired — start again.');

        $google2fa = new Google2FA();
        if (! $google2fa->verifyKey($secret, $data['code'])) {
            return back()->withErrors(['code' => 'That code is incorrect. Check your app and try again.']);
        }

        $recoveryCodes = collect(range(1, 8))
            ->map(fn () => Str::upper(Str::random(4) . '-' . Str::random(4)))
            ->all();

        $user = auth()->user();
        $user->two_factor_secret = $secret;
        $user->two_factor_enabled = true;
        // Store recovery codes hashed, just like a password — even if the
        // database leaked, no one could read the actual codes back out.
        $user->two_factor_recovery_codes = collect($recoveryCodes)->map(fn ($c) => Hash::make($c))->all();
        $user->save();

        $request->session()->forget('2fa.pending_secret');

        // Show the plain codes ONE time only — after this they're gone forever
        // (only the hashes remain, which can verify a code but not display it).
        return view('system.2fa-recovery-codes', ['recoveryCodes' => $recoveryCodes]);
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = auth()->user();
        $user->two_factor_secret = null;
        $user->two_factor_enabled = false;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return redirect()->route('admin.system.2fa')->with('status', 'Two-factor authentication disabled.');
    }
}
