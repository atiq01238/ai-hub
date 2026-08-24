<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Frontend\PendingUserActionService;
use App\Services\EmailIntelligenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    private const PROVIDERS = ['google', 'apple'];

    public function redirect(Request $request, string $provider)
    {
        $provider = $this->validatedProvider($provider);
        $origin = $request->query('origin') === 'signup' ? 'signup' : 'login';
        $request->session()->put('social_auth_origin', $origin);

        if (! $this->providerIsConfigured($provider)) {
            return redirect()
                ->route($origin)
                ->withErrors(['social' => ucfirst($provider).' sign in is not configured yet. Add the provider credentials and try again.']);
        }

        $driver = Socialite::driver($provider);

        if ($provider === 'google') {
            return $driver
                ->scopes(['openid', 'profile', 'email'])
                ->with(['prompt' => 'select_account'])
                ->redirect();
        }

        return $driver
            ->scopes(['name', 'email'])
            ->redirect();
    }

    public function callback(Request $request, string $provider)
    {
        $provider = $this->validatedProvider($provider);
        $origin = $request->session()->pull('social_auth_origin', 'login');
        $origin = $origin === 'signup' ? 'signup' : 'login';

        if ($request->filled('error')) {
            return redirect()
                ->route($origin)
                ->withErrors(['social' => 'The '.ucfirst($provider).' sign in request was cancelled or could not be completed.']);
        }

        try {
            $providerUser = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route($origin)
                ->withErrors(['social' => 'We could not verify your '.ucfirst($provider).' account. Please try again.']);
        }

        $providerUserId = trim((string) $providerUser->getId());
        $providerEmail = mb_strtolower(trim((string) $providerUser->getEmail()));

        if ($providerUserId === '') {
            return redirect()
                ->route($origin)
                ->withErrors(['social' => ucfirst($provider).' did not return a valid account identifier.']);
        }

        try {
            $user = DB::transaction(function () use ($provider, $providerUser, $providerUserId, $providerEmail): User {
                $account = SocialAccount::query()
                    ->where('provider', $provider)
                    ->where('provider_user_id', $providerUserId)
                    ->lockForUpdate()
                    ->first();

                if ($account) {
                    $account->forceFill([
                        'provider_email' => $providerEmail !== '' ? $providerEmail : $account->provider_email,
                        'avatar_url' => $providerUser->getAvatar() ?: $account->avatar_url,
                    ])->save();

                    return $account->user()->firstOrFail();
                }

                if ($providerEmail === '') {
                    throw new \RuntimeException('Social provider did not return an email address for a new account.');
                }

                $user = User::query()
                    ->whereRaw('LOWER(email) = ?', [$providerEmail])
                    ->lockForUpdate()
                    ->first();

                if (! $user) {
                    $displayName = trim((string) $providerUser->getName());
                    if ($displayName === '') {
                        $displayName = Str::headline(Str::before($providerEmail, '@')) ?: 'AI Orbit User';
                    }

                    $user = new User([
                        'name' => Str::limit($displayName, 255, ''),
                        'email' => $providerEmail,
                        // Social accounts still receive a strong unknown local password.
                        // They may create a local password later through Forgot Password.
                        'password' => Str::random(64),
                    ]);
                    $user->email_verified_at = now();
                    $user->save();
                } elseif (! $user->email_verified_at) {
                    // Google / Apple have already verified ownership of the returned email.
                    $user->forceFill(['email_verified_at' => now()])->save();
                }

                SocialAccount::create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_user_id' => $providerUserId,
                    'provider_email' => $providerEmail,
                    'avatar_url' => $providerUser->getAvatar(),
                ]);

                return $user;
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route($origin)
                ->withErrors(['social' => 'We could not connect that '.ucfirst($provider).' account to AI Orbit. Please try again.']);
        }

        $user->restoreIfSuspensionExpired();

        if ($user->status !== 'active') {
            LoginAttempt::create([
                'email' => $user->email,
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'successful' => false,
            ]);

            return redirect()
                ->route('login')
                ->withErrors(['social' => 'This account is suspended. Contact support if you believe this is a mistake.']);
        }

        LoginAttempt::create([
            'email' => $user->email,
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'successful' => true,
        ]);

        $emailIntelligence = app(EmailIntelligenceService::class);
        $emailIntelligence->ensurePreferences($user);
        $emailIntelligence->queueWelcome($user);

        if ($user->two_factor_enabled) {
            $request->session()->put('2fa_user_id', $user->id);
            $request->session()->put('2fa_remember', true);

            return redirect()->route('login.2fa');
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $completedAction = app(PendingUserActionService::class)->consume($request, $user);
        if ($completedAction) {
            $request->session()->flash('status', $completedAction['message']);
        }

        $destination = $user->role === 'admin'
            ? route('admin.dashboard')
            : (($user->preference?->onboarding_completed)
                ? route('home')
                : route('account.onboarding'));

        return redirect()->intended($destination);
    }

    private function validatedProvider(string $provider): string
    {
        $provider = mb_strtolower(trim($provider));
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return $provider;
    }

    private function providerIsConfigured(string $provider): bool
    {
        $config = config('services.'.$provider, []);

        if ($provider === 'google') {
            return filled($config['client_id'] ?? null)
                && filled($config['client_secret'] ?? null)
                && filled($config['redirect'] ?? null);
        }

        $hasSecret = filled($config['client_secret'] ?? null);
        $hasPrivateKey = filled($config['key_id'] ?? null)
            && filled($config['team_id'] ?? null)
            && filled($config['private_key'] ?? null);

        return filled($config['client_id'] ?? null)
            && filled($config['redirect'] ?? null)
            && ($hasSecret || $hasPrivateKey);
    }
}
