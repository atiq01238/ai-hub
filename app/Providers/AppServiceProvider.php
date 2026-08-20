<?php

namespace App\Providers;

use App\Models\Report;
use App\Models\Review;
use App\Models\Submission;
use App\Models\CommunityComment;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        \App\Models\Review::observe(\App\Observers\ReviewObserver::class);
        \App\Models\PricingPlan::observe(\App\Observers\PricingPlanObserver::class);
        \App\Models\Tool::observe(\App\Observers\FollowedEntityObserver::class);
        \App\Models\AiModel::observe(\App\Observers\FollowedEntityObserver::class);
        \App\Models\Company::observe(\App\Observers\FollowedEntityObserver::class);

        View::composer('partials.sidebar', function ($view) {
            $counts = [
                'reviews' => 0,
                'submissions' => 0,
                'reports' => 0,
                'comments' => 0,
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

            $view->with('communityNavCounts', $counts);
        });
    }
}
