<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\Company;
use App\Models\CommunityComment;
use App\Models\NewsItem;
use App\Models\Review;
use App\Models\SavedItem;
use App\Models\Tool;
use App\Models\UserComparison;
use App\Models\UserInteraction;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use App\Services\Frontend\RecommendationService;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $userId = (int) $user->getAuthIdentifier();
        $preference = $user->preference;
        $onboardingComplete = (bool) ($preference?->onboarding_completed);
        $recommendations = app(RecommendationService::class)->for($user);

        $stats = [
            'saved' => SavedItem::where('user_id', $userId)->count(),
            'reviews' => Review::where('user_id', $userId)->where('review_type', 'user')->count(),
            'following' => UserInteraction::where('user_id', $userId)->where('action', 'follow')->count(),
            'comparisons' => UserComparison::where('user_id', $userId)->where('is_saved', true)->count(),
            'tests' => UserInteraction::where('user_id', $userId)->where('action', 'test_viewed')->count(),
        ];

        $savedBreakdown = SavedItem::query()
            ->where('user_id', $userId)
            ->selectRaw('saveable_type, COUNT(*) as total')
            ->groupBy('saveable_type')
            ->pluck('total', 'saveable_type');

        $recentActivity = $this->recentActivity($userId);
        $continueItems = $this->continueItems($userId);
        $weeklyActivity = $this->weeklyActivity($userId);
        $reviewStatus = Review::query()
            ->where('user_id', $userId)
            ->where('review_type', 'user')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('frontend.account.dashboard', compact(
            'user',
            'stats',
            'savedBreakdown',
            'recentActivity',
            'continueItems',
            'weeklyActivity',
            'reviewStatus',
            'preference',
            'onboardingComplete',
            'recommendations'
        ));
    }

    public function reviews(Request $request): View
    {
        $reviews = Review::query()
            ->with(['tool.company', 'model.company'])
            ->where('user_id', $request->user()->id)
            ->where('review_type', 'user')
            ->latest()
            ->paginate(12);

        $counts = Review::query()
            ->where('user_id', $request->user()->id)
            ->where('review_type', 'user')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('frontend.account.reviews', compact('reviews', 'counts'));
    }


    public function comments(Request $request): View
    {
        $comments = CommunityComment::query()
            ->where('user_id', $request->user()->id)
            ->withCount([
                'replies as reply_count' => fn ($query) => $query->where('status', 'published'),
                'reports',
            ])
            ->latest()
            ->paginate(20);

        return view('frontend.account.comments', compact('comments'));
    }

    public function following(Request $request): View
    {
        $following = UserInteraction::query()
            ->where('user_id', $request->user()->id)
            ->where('action', 'follow')
            ->latest()
            ->paginate(18);

        $targets = $this->resolveFollowingTargets($following->getCollection());
        $following->setCollection(
            $following->getCollection()->map(function (UserInteraction $row) use ($targets) {
                $row->setAttribute('resolved_target', $targets[$row->target_type][$row->target_id] ?? null);
                return $row;
            })
        );

        return view('frontend.account.following', compact('following'));
    }

    public function activity(Request $request): View
    {
        $activity = $this->recentActivity((int) $request->user()->id, 60);

        return view('frontend.account.activity', compact('activity'));
    }

    public function settings(Request $request): View
    {
        return view('frontend.account.settings', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $request->user()->update(['name' => trim($data['name'])]);

        return back()->with('status', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return back()->with('status', 'Password changed successfully.');
    }

    private function recentActivity(int $userId, int $limit = 12): Collection
    {
        $activities = collect();

        SavedItem::query()
            ->where('user_id', $userId)
            ->with(['saveable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Tool::class => ['company'],
                    AiModel::class => ['company'],
                    NewsItem::class => [],
                    Article::class => [],
                    Company::class => [],
                ]);
            }])
            ->latest()
            ->limit($limit)
            ->get()
            ->each(function (SavedItem $saved) use ($activities) {
                if (! $saved->saveable) {
                    return;
                }

                $activities->push([
                    'kind' => 'saved',
                    'icon' => 'bookmark',
                    'title' => 'Saved ' . ($saved->saveable->name ?? $saved->saveable->headline ?? $saved->saveable->title ?? 'item'),
                    'subtitle' => class_basename($saved->saveable_type),
                    'url' => $this->saveableUrl($saved->saveable),
                    'at' => $saved->created_at,
                ]);
            });

        Review::query()
            ->with('tool')
            ->where('user_id', $userId)
            ->where('review_type', 'user')
            ->latest()
            ->limit($limit)
            ->get()
            ->each(function (Review $review) use ($activities) {
                $activities->push([
                    'kind' => 'review',
                    'icon' => 'star',
                    'title' => 'Rated ' . ($review->model?->name ?? $review->tool?->name ?? 'an AI item'),
                    'subtitle' => number_format((float) $review->rating, 1) . '/5 · ' . ucfirst($review->status),
                    'url' => $review->status === 'published' && $review->tool
                        ? route('reviews.show', $review)
                        : ($review->model
                            ? url('/models/' . $review->model->getRouteKey() . '/review')
                            : ($review->tool
                                ? url('/tools/' . $review->tool->getRouteKey() . '/review')
                                : route('account.reviews'))),
                    'at' => $review->updated_at,
                ]);
            });


        CommunityComment::query()
            ->where('user_id', $userId)
            ->latest()
            ->limit($limit)
            ->get()
            ->each(function (CommunityComment $comment) use ($activities) {
                $activities->push([
                    'kind' => 'comment',
                    'icon' => 'message-square',
                    'title' => ($comment->parent_id ? 'Replied in ' : 'Commented on ') . ucfirst($comment->commentable_type),
                    'subtitle' => ucfirst($comment->status) . ' · ' . \Illuminate\Support\Str::limit($comment->body, 70),
                    'url' => route('account.comments'),
                    'at' => $comment->updated_at,
                ]);
            });

        UserInteraction::query()
            ->where('user_id', $userId)
            ->whereIn('action', ['follow', 'helpful', 'test_viewed'])
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->each(function (UserInteraction $interaction) use ($activities) {
                $activities->push([
                    'kind' => $interaction->action,
                    'icon' => match ($interaction->action) {
                        'follow' => 'bell-plus',
                        'helpful' => 'thumbs-up',
                        default => 'flask-conical',
                    },
                    'title' => match ($interaction->action) {
                        'follow' => 'Followed a ' . ucfirst($interaction->target_type),
                        'helpful' => 'Marked a review helpful',
                        default => 'Viewed a Test Lab experiment',
                    },
                    'subtitle' => ucfirst(str_replace('_', ' ', $interaction->action)),
                    'url' => $interaction->action === 'test_viewed'
                        ? route('testlab.show', $interaction->target_id)
                        : route('account.following'),
                    'at' => $interaction->updated_at,
                ]);
            });

        UserComparison::query()
            ->where('user_id', $userId)
            ->latest('last_viewed_at')
            ->limit($limit)
            ->get()
            ->each(function (UserComparison $comparison) use ($activities) {
                $activities->push([
                    'kind' => 'comparison',
                    'icon' => 'scale',
                    'title' => ($comparison->is_saved ? 'Saved ' : 'Compared ') . $comparison->title,
                    'subtitle' => ucfirst($comparison->comparable_type) . ' comparison',
                    'url' => $this->comparisonUrl($comparison),
                    'at' => $comparison->last_viewed_at ?: $comparison->updated_at,
                ]);
            });

        return $activities
            ->filter(fn ($item) => $item['at'])
            ->sortByDesc(fn ($item) => $item['at']->timestamp)
            ->take($limit)
            ->values();
    }

    private function continueItems(int $userId): Collection
    {
        $items = collect();

        SavedItem::query()
            ->where('user_id', $userId)
            ->with(['saveable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Tool::class => ['company'],
                    AiModel::class => ['company'],
                    NewsItem::class => [],
                    Article::class => [],
                    Company::class => [],
                ]);
            }])
            ->latest()
            ->limit(4)
            ->get()
            ->each(function (SavedItem $saved) use ($items) {
                if (! $saved->saveable) {
                    return;
                }

                $target = $saved->saveable;
                $items->push([
                    'label' => class_basename($saved->saveable_type),
                    'name' => $target->name ?? $target->headline ?? $target->title ?? 'Saved item',
                    'meta' => 'Saved ' . $saved->created_at->diffForHumans(),
                    'url' => $this->saveableUrl($target),
                    'initial' => strtoupper(substr($target->name ?? $target->headline ?? $target->title ?? 'AI', 0, 2)),
                ]);
            });

        if ($items->count() < 4) {
            UserComparison::query()
                ->where('user_id', $userId)
                ->whereNotNull('last_viewed_at')
                ->latest('last_viewed_at')
                ->limit(4 - $items->count())
                ->get()
                ->each(function (UserComparison $comparison) use ($items) {
                    $items->push([
                        'label' => 'Comparison',
                        'name' => $comparison->title,
                        'meta' => 'Viewed ' . optional($comparison->last_viewed_at)->diffForHumans(),
                        'url' => $this->comparisonUrl($comparison),
                        'initial' => 'VS',
                    ]);
                });
        }

        return $items->take(4)->values();
    }

    private function weeklyActivity(int $userId): array
    {
        $days = collect(range(6, 0))->map(fn ($offset) => now()->subDays($offset)->startOfDay());
        $result = [];

        foreach ($days as $day) {
            $next = $day->copy()->addDay();

            $count =
                SavedItem::where('user_id', $userId)->whereBetween('created_at', [$day, $next])->count()
                + Review::where('user_id', $userId)->whereBetween('updated_at', [$day, $next])->count()
                + UserInteraction::where('user_id', $userId)->whereBetween('updated_at', [$day, $next])->count()
                + UserComparison::where('user_id', $userId)->whereBetween('updated_at', [$day, $next])->count();

            $result[] = [
                'label' => $day->format('D'),
                'value' => $count,
            ];
        }

        return $result;
    }

    private function resolveFollowingTargets(Collection $rows): array
    {
        $grouped = $rows->groupBy('target_type');
        $resolved = [];

        foreach ([
            'tool' => Tool::class,
            'model' => AiModel::class,
            'company' => Company::class,
        ] as $type => $class) {
            $ids = collect($grouped->get($type, []))->pluck('target_id')->filter()->unique()->values();

            if ($ids->isEmpty()) {
                continue;
            }

            $resolved[$type] = $class::query()
                ->whereIn('id', $ids)
                ->get()
                ->keyBy('id')
                ->all();
        }

        return $resolved;
    }

    private function saveableUrl(object $saveable): string
    {
        return match (true) {
            $saveable instanceof Tool => route('tools.show', $saveable),
            $saveable instanceof AiModel => route('models.show', $saveable),
            $saveable instanceof NewsItem => route('news.show', $saveable),
            $saveable instanceof Article => route('articles.show', $saveable),
            $saveable instanceof Company => route('companies.show', $saveable),
            default => route('saved.index'),
        };
    }

    private function comparisonUrl(UserComparison $comparison): string
    {
        if ($comparison->comparison_id) {
            $comparison->loadMissing('comparison');

            if ($comparison->comparison) {
                return route('comparisons.show', $comparison->comparison);
            }
        }

        return route('comparisons.preview') . '?' . http_build_query([
            'type' => $comparison->comparable_type,
            'items' => $comparison->item_ids,
        ]);
    }
}
