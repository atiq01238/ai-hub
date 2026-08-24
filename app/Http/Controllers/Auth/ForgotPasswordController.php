<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Throwable;

class ForgotPasswordController extends Controller
{
    public function show()
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $status = Password::broker()->sendResetLink([
                'email' => mb_strtolower(trim($validated['email'])),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'We could not send the reset email right now. Please try again shortly.']);
        }

        // Do not reveal whether an email address is registered.
        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withInput($request->only('email'))
                ->with('status', 'If an AI Orbit account exists for that email, a reset link was already requested. Please check your inbox or try again shortly.');
        }

        return back()
            ->withInput($request->only('email'))
            ->with('status', 'If an AI Orbit account exists for that email, we sent a secure password reset link.');
    }
}
