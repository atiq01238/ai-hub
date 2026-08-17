<?php

namespace App\Services\Analytics;

use App\Models\Article;
use App\Models\Comparison;
use App\Models\Review;
use App\Models\SocialPost;
use App\Models\Tool;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public function dashboard(string $tab, int $days = 30): array
    {
        $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
        $to = now()->endOfDay();
        $from = now()->subDays($days - 1)->startOfDay();
        $previousTo = $from->copy()->subSecond();
        $previousFrom = $previousTo->copy()->subDays($days - 1)->startOfDay();

        $base = [
            'tab' => $tab,
            'days' => $days,
            'period' => [
                'from' => $from,
                'to' => $to,
                'previous_from' => $previousFrom,
                'previous_to' => $previousTo,
                'label' => $days === 365 ? 'Last 12 months' : "Last {$days} days",
            ],
        ];

        return array_merge($base, match ($tab) {
            'tools' => $this->tools($from, $to, $previousFrom, $previousTo),
            'search' => $this->search($from, $to),
            'comparisons' => $this->comparisons($from, $to, $previousFrom, $previousTo),
            'content' => $this->content($from, $to, $previousFrom, $previousTo),
            'trending' => $this->trending($from, $to),
            default => $this->website($from, $to, $previousFrom, $previousTo),
        });
    }

    public function exportRows(string $tab, int $days = 30): array
    {
        $data = $this->dashboard($tab, $days);

        $rows = [
            ['Analytics Report', ucfirst($tab)],
            ['Period', $data['period']['label']],
            ['Generated At', now()->toDateTimeString()],
            [],
            ['Metric', 'Value', 'Change vs previous period'],
        ];

        foreach ($data['kpis'] as $kpi) {
            $rows[] = [$kpi['label'], $kpi['raw_value'] ?? $kpi['value'], $kpi['delta_label'] ?? '—'];
        }

        if (! empty($data['table']['rows'])) {
            $rows[] = [];
            $rows[] = $data['table']['headers'];
            foreach ($data['table']['rows'] as $row) {
                $rows[] = array_values($row);
            }
        }

        return $rows;
    }

    private function website(Carbon $from, Carbon $to, Carbon $previousFrom, Carbon $previousTo): array
    {
        $newUsers = User::whereBetween('created_at', [$from, $to])->count();
        $previousUsers = User::whereBetween('created_at', [$previousFrom, $previousTo])->count();
        $activeUsers = User::where('status', 'active')->count();
        $totalUsers = User::count();
        $publishedTools = Tool::where('status', 'published')->count();
        $publishedArticles = Article::where('status', 'published')->count();

        $trend = $this->dailyTrend($from, $to, fn (Carbon $date) => User::whereDate('created_at', $date)->count());

        return [
            'kpis' => [
                $this->kpi('New Users', $newUsers, 'users', $this->delta($newUsers, $previousUsers)),
                $this->kpi('Active Users', $activeUsers, 'user-check', null),
                $this->kpi('Published Tools', $publishedTools, 'wrench', null),
                $this->kpi('Published Articles', $publishedArticles, 'file-text', null),
            ],
            'chart' => ['title' => 'User Growth', 'series_label' => 'New users', 'points' => $trend],
            'table' => [
                'title' => 'Platform Snapshot',
                'headers' => ['Metric', 'Value', 'Status'],
                'rows' => [
                    ['metric' => 'Total registered users', 'value' => number_format($totalUsers), 'status' => 'Live'],
                    ['metric' => 'Active users', 'value' => number_format($activeUsers), 'status' => 'Live'],
                    ['metric' => 'Published tools', 'value' => number_format($publishedTools), 'status' => 'Live'],
                    ['metric' => 'Published articles', 'value' => number_format($publishedArticles), 'status' => 'Live'],
                ],
            ],
            'readiness' => [
                'level' => 'partial',
                'title' => 'Traffic tracking not connected',
                'message' => 'Visitors, page views, sessions, referrers and CTR require a page/event tracking source. Current cards use verified application database metrics only.',
            ],
        ];
    }

    private function tools(Carbon $from, Carbon $to, Carbon $previousFrom, Carbon $previousTo): array
    {
        $published = Tool::where('status', 'published')->count();
        $newPublished = Tool::where('status', 'published')->whereBetween('published_at', [$from, $to])->count();
        $previousNew = Tool::where('status', 'published')->whereBetween('published_at', [$previousFrom, $previousTo])->count();
        $avgRating = (float) Tool::where('status', 'published')->avg('rating');
        $reviews = Review::published()->whereBetween('created_at', [$from, $to])->count();
        $previousReviews = Review::published()->whereBetween('created_at', [$previousFrom, $previousTo])->count();

        $trend = $this->dailyTrend($from, $to, fn (Carbon $date) => Tool::where('status', 'published')->whereDate('published_at', $date)->count());

        $topTools = Tool::query()
            ->where('status', 'published')
            ->withCount(['reviews' => fn (Builder $q) => $q->published()])
            ->orderByDesc('popularity')
            ->orderByDesc('rating')
            ->limit(8)
            ->get();

        return [
            'kpis' => [
                $this->kpi('Published Tools', $published, 'wrench', null),
                $this->kpi('Newly Published', $newPublished, 'sparkles', $this->delta($newPublished, $previousNew)),
                $this->kpi('Average Rating', round($avgRating, 1), 'star', null, 1),
                $this->kpi('Published Reviews', $reviews, 'message-square', $this->delta($reviews, $previousReviews)),
            ],
            'chart' => ['title' => 'Tool Publishing Trend', 'series_label' => 'Published tools', 'points' => $trend],
            'table' => [
                'title' => 'Top Tools',
                'headers' => ['Tool', 'Popularity', 'Rating', 'Reviews'],
                'rows' => $topTools->map(fn (Tool $tool) => [
                    'tool' => $tool->name,
                    'popularity' => number_format((int) $tool->popularity),
                    'rating' => number_format((float) $tool->rating, 1),
                    'reviews' => number_format((int) $tool->reviews_count),
                ])->all(),
            ],
            'readiness' => [
                'level' => 'partial',
                'title' => 'Tool engagement tracking can be expanded',
                'message' => 'Ratings, reviews, popularity and publishing data are live. Per-tool page views, outbound clicks and compare clicks are not stored in the current schema.',
            ],
        ];
    }

    private function comparisons(Carbon $from, Carbon $to, Carbon $previousFrom, Carbon $previousTo): array
    {
        $views = (int) Comparison::where('status', 'published')->sum('views');
        $built = Comparison::whereBetween('created_at', [$from, $to])->count();
        $previousBuilt = Comparison::whereBetween('created_at', [$previousFrom, $previousTo])->count();
        $published = Comparison::where('status', 'published')->count();
        $drafts = Comparison::where('status', 'draft')->count();

        $trend = $this->dailyTrend($from, $to, fn (Carbon $date) => Comparison::whereDate('created_at', $date)->count());
        $top = Comparison::where('status', 'published')->orderByDesc('views')->limit(8)->get();

        return [
            'kpis' => [
                $this->kpi('Recorded Views', $views, 'eye', null),
                $this->kpi('Built This Period', $built, 'square-stack', $this->delta($built, $previousBuilt)),
                $this->kpi('Published Comparisons', $published, 'circle-check', null),
                $this->kpi('Draft Comparisons', $drafts, 'file-clock', null),
            ],
            'chart' => ['title' => 'Comparison Creation Trend', 'series_label' => 'Comparisons created', 'points' => $trend],
            'table' => [
                'title' => 'Most Viewed Comparisons',
                'headers' => ['Comparison', 'Type', 'Views', 'Status'],
                'rows' => $top->map(fn (Comparison $comparison) => [
                    'comparison' => $comparison->title,
                    'type' => ucfirst($comparison->comparable_type),
                    'views' => number_format((int) $comparison->views),
                    'status' => ucfirst($comparison->status),
                ])->all(),
            ],
            'readiness' => [
                'level' => 'good',
                'title' => 'Comparison analytics are partially event-backed',
                'message' => 'Total comparison views are stored. Shares, dwell time and per-period view history are not currently persisted, so they are intentionally not fabricated.',
            ],
        ];
    }

    private function content(Carbon $from, Carbon $to, Carbon $previousFrom, Carbon $previousTo): array
    {
        $publishedArticles = Article::where('status', 'published')->count();
        $periodArticles = Article::where('status', 'published')->whereBetween('published_at', [$from, $to])->count();
        $previousArticles = Article::where('status', 'published')->whereBetween('published_at', [$previousFrom, $previousTo])->count();
        $publishedReviews = Review::published()->count();
        $periodReviews = Review::published()->whereBetween('created_at', [$from, $to])->count();
        $previousReviews = Review::published()->whereBetween('created_at', [$previousFrom, $previousTo])->count();
        $socialPosts = SocialPost::count();
        $approvalQueue = Article::whereIn('approval_status', ['in_review', 'needs_changes'])->count();

        $trend = $this->dailyTrend($from, $to, fn (Carbon $date) => Article::where('status', 'published')->whereDate('published_at', $date)->count());
        $latest = Article::with('author')->where('status', 'published')->orderByDesc('published_at')->limit(8)->get();

        return [
            'contentMetrics' => [
                'published_articles' => $publishedArticles,
                'scheduled_articles' => Article::where('status', 'scheduled')->count(),
                'pending_reviews' => Review::where('status', 'pending')->count(),
                'published_reviews' => $publishedReviews,
                'social_posts' => $socialPosts,
                'approval_queue' => $approvalQueue,
            ],
            'contentTrend' => collect($trend)->map(fn ($point) => ['label' => $point['label'], 'value' => $point['value']]),
            'kpis' => [
                $this->kpi('Published Articles', $publishedArticles, 'file-text', $this->delta($periodArticles, $previousArticles)),
                $this->kpi('Published Reviews', $publishedReviews, 'star', $this->delta($periodReviews, $previousReviews)),
                $this->kpi('Social Posts', $socialPosts, 'share-2', null),
                $this->kpi('Approval Queue', $approvalQueue, 'list-checks', null),
            ],
            'chart' => ['title' => 'Publishing Trend', 'series_label' => 'Published articles', 'points' => $trend],
            'table' => [
                'title' => 'Latest Published Content',
                'headers' => ['Article', 'Author', 'Published', 'Status'],
                'rows' => $latest->map(fn (Article $article) => [
                    'article' => $article->title,
                    'author' => $article->author?->name ?? '—',
                    'published' => $article->published_at?->format('M j, Y') ?? '—',
                    'status' => ucfirst($article->status),
                ])->all(),
            ],
            'readiness' => [
                'level' => 'good',
                'title' => 'Content analytics connected',
                'message' => 'Publishing, review moderation, social-post and approval-workflow metrics are calculated directly from the current database.',
            ],
        ];
    }

    private function search(Carbon $from, Carbon $to): array
    {
        return [
            'kpis' => [
                $this->kpi('Tracked Searches', 0, 'search', null),
                $this->kpi('Unique Queries', 0, 'text-search', null),
                $this->kpi('Zero-result Searches', 0, 'circle-x', null),
                $this->kpi('Search Conversion', '—', 'mouse-pointer-click', null, null, '—'),
            ],
            'chart' => ['title' => 'Search Activity', 'series_label' => 'Searches', 'points' => $this->dailyTrend($from, $to, fn () => 0)],
            'table' => ['title' => 'Top Search Queries', 'headers' => ['Query', 'Searches', 'Results', 'Conversion'], 'rows' => []],
            'readiness' => [
                'level' => 'missing',
                'title' => 'Search tracking source not connected',
                'message' => 'The latest project has no search-query/event table. This dashboard stays at zero until public search events are persisted, preventing misleading analytics.',
            ],
        ];
    }

    private function trending(Carbon $from, Carbon $to): array
    {
        $top = Tool::where('status', 'published')->orderByDesc('popularity')->orderByDesc('rating')->limit(10)->get();
        $recent = Tool::where('status', 'published')->whereBetween('published_at', [$from, $to])->count();

        return [
            'kpis' => [
                $this->kpi('High-Popularity Tools', Tool::where('status', 'published')->where('popularity', '>=', 75)->count(), 'flame', null),
                $this->kpi('New Tools This Period', $recent, 'sparkles', null),
                $this->kpi('Rated 4.5+', Tool::where('status', 'published')->where('rating', '>=', 4.5)->count(), 'star', null),
                $this->kpi('Published Catalog', Tool::where('status', 'published')->count(), 'library', null),
            ],
            'chart' => ['title' => 'New Tool Momentum', 'series_label' => 'Published tools', 'points' => $this->dailyTrend($from, $to, fn (Carbon $date) => Tool::where('status', 'published')->whereDate('published_at', $date)->count())],
            'table' => [
                'title' => 'Catalog Momentum Leaders',
                'headers' => ['Tool', 'Popularity', 'Rating', 'Published'],
                'rows' => $top->map(fn (Tool $tool) => [
                    'tool' => $tool->name,
                    'popularity' => number_format((int) $tool->popularity),
                    'rating' => number_format((float) $tool->rating, 1),
                    'published' => $tool->published_at?->format('M j, Y') ?? '—',
                ])->all(),
            ],
            'readiness' => [
                'level' => 'partial',
                'title' => 'Catalog trends available; search trends are not',
                'message' => 'Momentum currently uses tool popularity, rating and publishing data. True rising search terms require a search-event tracking table.',
            ],
        ];
    }

    private function dailyTrend(Carbon $from, Carbon $to, callable $resolver): array
    {
        $days = $from->diffInDays($to) + 1;
        $step = $days > 120 ? 7 : ($days > 45 ? 3 : 1);
        $points = [];

        for ($offset = 0; $offset < $days; $offset += $step) {
            $bucketStart = $from->copy()->addDays($offset);
            if ($step === 1) {
                $value = (int) $resolver($bucketStart);
            } else {
                $value = 0;
                for ($i = 0; $i < $step && $offset + $i < $days; $i++) {
                    $value += (int) $resolver($bucketStart->copy()->addDays($i));
                }
            }
            $points[] = ['label' => $bucketStart->format('M j'), 'value' => $value];
        }

        return $points;
    }

    private function delta(float|int $current, float|int $previous): ?array
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? ['value' => 100.0, 'direction' => 'up', 'label' => '+100%'] : null;
        }

        $change = (($current - $previous) / abs($previous)) * 100;
        return [
            'value' => round($change, 1),
            'direction' => $change >= 0 ? 'up' : 'down',
            'label' => ($change >= 0 ? '+' : '') . number_format($change, 1) . '%',
        ];
    }

    private function kpi(string $label, mixed $rawValue, string $icon, ?array $delta = null, ?int $decimals = 0, ?string $formatted = null): array
    {
        if ($formatted !== null) {
            $value = $formatted;
        } elseif (is_numeric($rawValue)) {
            $value = number_format((float) $rawValue, $decimals ?? 0);
        } else {
            $value = (string) $rawValue;
        }

        return [
            'label' => $label,
            'value' => $value,
            'raw_value' => $rawValue,
            'icon' => $icon,
            'delta' => $delta,
            'delta_label' => $delta['label'] ?? null,
        ];
    }
}
