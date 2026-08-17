<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $user?->restoreIfSuspensionExpired();

        if (! $user || $user->status === 'active') {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'This account is suspended.'], 403);
        }

        return redirect()->route('login')->withErrors([
            'email' => 'This account is suspended. Contact support if you believe this is a mistake.',
        ]);
    }
}
