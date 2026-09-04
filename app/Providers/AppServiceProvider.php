<?php

namespace App\Providers;

use App\Models\Report;
use App\Models\AiDiscovery;
use App\Models\Review;
use App\Models\Submission;
use App\Models\CommunityComment;
use App\Models\ContactMessage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Seo\SeoMetadataService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Keep signed links, canonical URLs, verification emails and queued mail
        // aligned with the production domain when AI Orbit is live behind a proxy.
        if ($this->app->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceRootUrl((string) config('app.url'));
            URL::forceScheme('https');
        }

        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event): void {
            $event->extendSocialite('apple', \SocialiteProviders\Apple\Provider::class);
        });

        \App\Models\Review::observe(\App\Observers\ReviewObserver::class);
        \App\Models\PricingPlan::observe(\App\Observers\PricingPlanObserver::class);
        \App\Models\Tool::observe(\App\Observers\FollowedEntityObserver::class);
        \App\Models\AiModel::observe(\App\Observers\FollowedEntityObserver::class);
        \App\Models\Company::observe(\App\Observers\FollowedEntityObserver::class);
        \App\Models\Tool::observe(\App\Observers\EmailIntelligenceObserver::class);
        \App\Models\AiModel::observe(\App\Observers\EmailIntelligenceObserver::class);
        \App\Models\NewsItem::observe(\App\Observers\EmailIntelligenceObserver::class);
        \App\Models\PricingHistory::observe(\App\Observers\PricingHistoryEmailObserver::class);
        \App\Models\BenchmarkResult::observe(\App\Observers\BenchmarkResultEmailObserver::class);

        Event::listen(\Illuminate\Auth\Events\Verified::class, function ($event): void {
            app(\App\Services\EmailIntelligenceService::class)->queueWelcome($event->user);
        });

        Event::listen(\Illuminate\Notifications\Events\NotificationSent::class, function ($event): void {
            $id = $event->notification->deliveryLogId ?? null;
            if ($event->channel === 'mail' && $id) {
                \App\Models\EmailDeliveryLog::whereKey($id)->update(['status'=>'sent','sent_at'=>now(),'error'=>null]);
            }
        });

        Event::listen(\Illuminate\Notifications\Events\NotificationFailed::class, function ($event): void {
            $id = $event->notification->deliveryLogId ?? null;
            if ($event->channel === 'mail' && $id) {
                \App\Models\EmailDeliveryLog::whereKey($id)->update([
                    'status'=>'failed','failed_at'=>now(),
                    'error'=>\Illuminate\Support\Str::limit((string)($event->data['exception'] ?? $event->data['message'] ?? 'Mail delivery failed.'),4000),
                ]);
            }
        });

        View::composer('partials.sidebar', function ($view) {
            $counts = [
                'reviews' => 0,
                'submissions' => 0,
                'reports' => 0,
                'comments' => 0,
                'contacts' => 0,
            ];

            if (Schema::hasTable('reviews')) {
                $reviewQuery = Review::where('status', 'pending');

                if (Schema::hasColumn('reviews', 'review_type')) {
                    $reviewQuery->where('review_type', 'user');
                }

                $counts['reviews'] = $reviewQuery->count();
            }

            if (Schema::hasTable('submissions')) {
                $counts['submissions'] = Submission::whereIn('status', ['pending', 'needs_info'])->count();
            }

            if (Schema::hasTable('community_comments')) {
                $counts['comments'] = CommunityComment::where('status','pending')->count();
            }

            if (Schema::hasTable('reports')) {
                $counts['reports'] = Report::open()->count();
            }

            if (Schema::hasTable('contact_messages')) {
                $counts['contacts'] = ContactMessage::where('status', 'new')->count();
            }

            $discoveryCounts = [
                'models' => 0,
                'tools' => 0,
                'updates' => 0,
                'total' => 0,
            ];

            if (Schema::hasTable('ai_discoveries')) {
                $grouped = AiDiscovery::query()
                    ->where('status', 'pending')
                    ->selectRaw('entity_type, COUNT(*) as aggregate')
                    ->groupBy('entity_type')
                    ->pluck('aggregate', 'entity_type');

                $discoveryCounts['models'] = (int) ($grouped['model'] ?? 0);
                $discoveryCounts['tools'] = (int) ($grouped['tool'] ?? 0);
                $discoveryCounts['updates'] = (int) (($grouped['model_update'] ?? 0) + ($grouped['tool_update'] ?? 0));
                $discoveryCounts['total'] = $discoveryCounts['models'] + $discoveryCounts['tools'] + $discoveryCounts['updates'];
            }

            $view->with('communityNavCounts', $counts);
            $view->with('discoveryNavCounts', $discoveryCounts);
        });
    }
}
