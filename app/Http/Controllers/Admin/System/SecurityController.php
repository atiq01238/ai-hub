<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SecurityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $hasAttempts = Schema::hasTable('login_attempts');
        $recentAttempts = $hasAttempts ? LoginAttempt::latest()->take(30)->get() : collect();
        $failed24h = $hasAttempts ? LoginAttempt::where('successful', false)->where('created_at', '>=', now()->subDay())->count() : 0;
        $failed7d = $hasAttempts ? LoginAttempt::where('successful', false)->where('created_at', '>=', now()->subDays(7))->count() : 0;
        $suspiciousIps = $hasAttempts ? LoginAttempt::select('ip_address', DB::raw('COUNT(*) as attempts'))
            ->where('successful', false)->where('created_at', '>=', now()->subDay())->whereNotNull('ip_address')
            ->groupBy('ip_address')->having('attempts', '>=', 5)->orderByDesc('attempts')->limit(10)->get() : collect();

        $usingDatabaseSessions = config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'));
        $activeSessions = $usingDatabaseSessions ? DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->orderByDesc('last_activity')->get() : collect();
        $allAdminSessions = $usingDatabaseSessions ? DB::table(config('session.table', 'sessions'))->whereNotNull('user_id')->count() : 0;

        $admins = User::where('role', 'admin');
        $adminCount = (clone $admins)->count();
        $adminsWithout2fa = Schema::hasColumn('users', 'two_factor_enabled') ? (clone $admins)->where('two_factor_enabled', false)->count() : 0;
        $twoFactorCompliance = $adminCount ? (int) round((($adminCount - $adminsWithout2fa) / $adminCount) * 100) : 100;
        $require2fa = Schema::hasTable('settings') ? Setting::get('require_2fa_for_admins', '1') === '1' : false;

        $riskPoints = min(40, $failed24h * 2) + min(25, $suspiciousIps->count() * 5) + ($adminsWithout2fa > 0 ? 20 : 0) + ($require2fa ? 0 : 15);
        $securityScore = max(0, 100 - $riskPoints);

        return view('system.security', compact(
            'recentAttempts', 'failed24h', 'failed7d', 'suspiciousIps', 'usingDatabaseSessions', 'activeSessions',
            'allAdminSessions', 'user', 'adminCount', 'adminsWithout2fa', 'twoFactorCompliance', 'require2fa', 'securityScore'
        ));
    }

    public function revokeSession(Request $request, string $sessionId)
    {
        abort_unless(config('session.driver') === 'database', 422, 'Database sessions are required.');
        DB::table(config('session.table', 'sessions'))->where('id', $sessionId)->where('user_id', $request->user()->id)->delete();
        return redirect()->back()->with('status', 'Session revoked.');
    }
}
