<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\EmailIntelligenceService;

class SignupController extends Controller
{
    // Show signup page
    public function index()
    {
        return view('auth.signup');
    }

    // Store new user
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'terms' => 'required|accepted',
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => mb_strtolower(trim($validated['email'])),
            'password' => $validated['password'],
        ]);

        app(EmailIntelligenceService::class)->ensurePreferences($user);
        Auth::login($user);
        $request->session()->regenerate();
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice')
            ->with('status', 'verification-link-sent');
    }
}