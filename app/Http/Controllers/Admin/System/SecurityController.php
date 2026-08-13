<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $recentAttempts = LoginAttempt::latest()->take(20)->get();

        $failed24h = LoginAttempt::where('successful', false)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        // Active sessions only work if the app uses the "database" session
        // driver — that's the only way to actually query who's logged in.
        $usingDatabaseSessions = config('session.driver') === 'database';
        $activeSessions = collect();

        if ($usingDatabaseSessions) {
            $activeSessions = DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->get();
        }

        return view('system.security', compact(
            'recentAttempts', 'failed24h', 'usingDatabaseSessions', 'activeSessions', 'user'
        ));
    }

    public function revokeSession(Request $request, string $sessionId)
    {
        DB::table(config('session.table', 'sessions'))
            ->where('id', $sessionId)
            ->where('user_id', $request->user()->id) // only ever your own sessions
            ->delete();

        return redirect()->back()->with('status', 'Session revoked.');
    }
}
