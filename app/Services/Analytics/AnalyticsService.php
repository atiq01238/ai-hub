<?php

namespace App\Services\Analytics;

use App\Models\AiModel;
use App\Models\AnalyticsPageView;
use App\Models\AnalyticsSession;
use App\Models\AnalyticsVisitor;
use App\Models\Article;
use App\Models\Comparison;
use App\Models\Review;
use App\Models\SearchEvent;
use App\Models\SocialPost;
use App\Models\Tool;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class AnalyticsService
{
    public function dashboard(string $tab, int $days = 30): array
    {
        $days = in_array($days, [1, 7, 30, 90, 365], true) ? $days : 30;
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
                'label' => $days === 1 ? 'Today' : ($days === 365 ? 'Last 12 months' : "Last {$days} days"),
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
        if (! $this->visitorAnalyticsReady()) {
            return $this->websiteWithoutTraffic($from, $to, $previousFrom, $previousTo);
        }

        $pageViews = AnalyticsPageView::query()->whereBetween('viewed_at', [$from, $to])->count();
        $previousPageViews = AnalyticsPageView::query()->whereBetween('viewed_at', [$previousFrom, $previousTo])->count();

        $uniqueVisitors = AnalyticsPageView::query()
            ->whereBetween('viewed_at', [$from, $to])
            ->distinct('visitor_id')
            ->count('visitor_id');
        $previousVisitors = AnalyticsPageView::query()
            ->whereBetween('viewed_at', [$previousFrom, $previousTo])
            ->distinct('visitor_id')
            ->count('visitor_id');

        $sessionsQuery = AnalyticsSession::query()->whereBetween('started_at', [$from, $to]);
        $sessions = (clone $sessionsQuery)->count();
        $previousSessions = AnalyticsSession::query()->whereBetween('started_at', [$previousFrom, $previousTo])->count();
        $bounceSessions = (clone $sessionsQuery)->where('page_views', '<=', 1)->count();
        $bounceRate = $sessions > 0 ? round(($bounceSessions / $sessions) * 100, 1) : 0.0;

        $newVisitors = AnalyticsVisitor::query()->whereBetween('first_seen_at', [$from, $to])->count();
        $newVisitors = min($newVisitors, $uniqueVisitors);
        $returningVisitors = max(0, $uniqueVisitors - $newVisitors);
        $pagesPerSession = $sessions > 0 ? round($pageViews / $sessions, 2) : 0.0;
        $recentVisitors = AnalyticsSession::query()
            ->where('last_seen_at', '>=', now()->subMinutes(5))
            ->distinct('visitor_id')
            ->count('visitor_id');

        [$trend, $visitorTrend] = $this->visitorTrend($from, $to);

        $topPages = AnalyticsPageView::query()
            ->whereBetween('viewed_at', [$from, $to])
            ->selectRaw('path, route_name, COUNT(*) as views, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('path', 'route_name')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $activeSessions = AnalyticsSession::query()
            ->where('started_at', '<=', $to)
            ->where('last_seen_at', '>=', $from);

        $sourceRows = (clone $activeSessions)
            ->selectRaw('referrer_domain, COUNT(*) as aggregate')
            ->groupBy('referrer_domain')
            ->orderByDesc('aggregate')
            ->limit(6)
            ->get();
        $deviceRows = (clone $activeSessions)
            ->selectRaw('device_type, COUNT(*) as aggregate')
            ->groupBy('device_type')
            ->orderByDesc('aggregate')
            ->get();
        $browserRows = (clone $activeSessions)
            ->selectRaw('browser, COUNT(*) as aggregate')
            ->groupBy('browser')
            ->orderByDesc('aggregate')
            ->limit(6)
            ->get();
        $countryRows = (clone $activeSessions)
            ->selectRaw('country_code, COUNT(*) as aggregate')
            ->groupBy('country_code')
            ->orderByDesc('aggregate')
            ->limit(6)
            ->get();

        $entityRows = AnalyticsPageView::query()
            ->whereBetween('viewed_at', [$from, $to])
            ->whereIn('entity_type', ['tool', 'model'])
            ->whereNotNull('entity_id')
            ->selectRaw('entity_type, entity_id, COUNT(*) as aggregate')
            ->groupBy('entity_type', 'entity_id')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get();

        $toolNames = Tool::query()
            ->whereIn('id', $entityRows->where('entity_type', 'tool')->pluck('entity_id'))
            ->pluck('name', 'id');
        $modelNames = AiModel::query()
            ->whereIn('id', $entityRows->where('entity_type', 'model')->pluck('entity_id'))
            ->pluck('name', 'id');

        $activeSessionCount = max(1, (clone $activeSessions)->count());

        return [
            'kpis' => [
                $this->kpi('Unique Visitors', $uniqueVisitors, 'users', $this->delta($uniqueVisitors, $previousVisitors)),
                $this->kpi('Page Views', $pageViews, 'eye', $this->delta($pageViews, $previousPageViews)),
                $this->kpi('Sessions', $sessions, 'mouse-pointer-2', $this->delta($sessions, $previousSessions)),
                $this->kpi('Bounce Rate', $bounceRate, 'corner-up-left', null, 1, number_format($bounceRate, 1) . '%'),
            ],
            'chart' => [
                'title' => 'Traffic Trend',
                'series_label' => 'Page views',
                'points' => $trend,
                'secondary_label' => 'Unique visitors',
                'secondary_points' => $visitorTrend,
            ],
            'table' => [
                'title' => 'Top Pages',
                'headers' => ['Page', 'Views', 'Visitors', 'Route'],
                'rows' => $topPages->map(fn ($row) => [
                    'page' => $row->path,
                    'views' => number_format((int) $row->views),
                    'visitors' => number_format((int) $row->visitors),
                    'route' => $row->route_name ?: '—',
                ])->all(),
            ],
            'websiteBreakdown' => [
                'recent_visitors' => $recentVisitors,
                'new_visitors' => $newVisitors,
                'returning_visitors' => $returningVisitors,
                'pages_per_session' => $pagesPerSession,
                'audience' => [
                    $this->breakdownRow('New visitors', $newVisitors, max(1, $uniqueVisitors)),
                    $this->breakdownRow('Returning visitors', $returningVisitors, max(1, $uniqueVisitors)),
                ],
                'devices' => $deviceRows->map(fn ($row) => $this->breakdownRow(ucfirst((string) $row->device_type), (int) $row->aggregate, $activeSessionCount))->all(),
                'browsers' => $browserRows->map(fn ($row) => $this->breakdownRow($row->browser ?: 'Other', (int) $row->aggregate, $activeSessionCount))->all(),
                'sources' => $sourceRows->map(fn ($row) => $this->breakdownRow($row->referrer_domain ?: 'Direct', (int) $row->aggregate, $activeSessionCount))->all(),
                'countries' => $countryRows->map(fn ($row) => $this->breakdownRow($row->country_code ?: 'Unknown', (int) $row->aggregate, $activeSessionCount))->all(),
                'entities' => $entityRows->map(function ($row) use ($toolNames, $modelNames, $pageViews) {
                    $name = $row->entity_type === 'tool'
                        ? ($toolNames[$row->entity_id] ?? 'Tool #' . $row->entity_id)
                        : ($modelNames[$row->entity_id] ?? 'Model #' . $row->entity_id);
                    return $this->breakdownRow($name . ' · ' . ucfirst($row->entity_type), (int) $row->aggregate, max(1, $pageViews));
                })->all(),
            ],
            'readiness' => [
                'level' => 'good',
                'title' => 'Native visitor analytics connected',
                'message' => 'Public human page views are tracked server-side. Admin/private routes, known bots, prefetch requests and Do Not Track visitors are excluded. Traffic history starts when this migration is installed.',
            ],
        ];
    }

    private function websiteWithoutTraffic(Carbon $from, Carbon $to, Carbon $previousFrom, Carbon $previousTo): array
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
                'level' => 'missing',
                'title' => 'Visitor analytics migration pending',
                'message' => 'Run php artisan migrate to activate visitor, session, page-view, referrer and device tracking. Until then this screen safely falls back to platform database metrics.',
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

        $toolPageViews = collect();
        if ($this->visitorAnalyticsReady()) {
            $toolPageViews = AnalyticsPageView::query()
                ->where('entity_type', 'tool')
                ->whereBetween('viewed_at', [$from, $to])
                ->whereIn('entity_id', $topTools->pluck('id'))
                ->selectRaw('entity_id, COUNT(*) as aggregate')
                ->groupBy('entity_id')
                ->pluck('aggregate', 'entity_id');
        }

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
                'headers' => ['Tool', 'Page Views', 'Popularity', 'Rating', 'Reviews'],
                'rows' => $topTools->map(fn (Tool $tool) => [
                    'tool' => $tool->name,
                    'page_views' => number_format((int) ($toolPageViews[$tool->id] ?? 0)),
                    'popularity' => number_format((int) $tool->popularity),
                    'rating' => number_format((float) $tool->rating, 1),
                    'reviews' => number_format((int) $tool->reviews_count),
                ])->all(),
            ],
            'readiness' => [
                'level' => $this->visitorAnalyticsReady() ? 'good' : 'partial',
                'title' => $this->visitorAnalyticsReady() ? 'Tool profile views connected' : 'Tool engagement tracking can be expanded',
                'message' => $this->visitorAnalyticsReady()
                    ? 'Public tool-profile page views now come from native visitor analytics; ratings, reviews and catalog signals remain database-backed.'
                    : 'Ratings, reviews, popularity and publishing data are live. Run the visitor analytics migration to add per-tool page views.',
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
        if (! Schema::hasTable('search_events')) {
            return [
                'kpis' => [
                    $this->kpi('Tracked Searches', 0, 'search', null),
                    $this->kpi('Unique Queries', 0, 'text-search', null),
                    $this->kpi('Zero-result Searches', 0, 'circle-x', null),
                    $this->kpi('Search Conversion', '—', 'mouse-pointer-click', null, null, '—'),
                ],
                'chart' => ['title' => 'Search Activity', 'series_label' => 'Searches', 'points' => $this->dailyTrend($from, $to, fn () => 0)],
                'table' => ['title' => 'Top Search Queries', 'headers' => ['Query', 'Searches', 'Avg. Results', 'Conversion'], 'rows' => []],
                'readiness' => [
                    'level' => 'missing',
                    'title' => 'Search event table unavailable',
                    'message' => 'Search analytics will activate when the search_events migration is present.',
                ],
            ];
        }

        $base = SearchEvent::query()->whereBetween('created_at', [$from, $to]);
        $searches = (clone $base)->count();
        $uniqueQueries = (clone $base)->distinct('query')->count('query');
        $zeroResults = (clone $base)->where('result_count', 0)->count();
        $clicks = (clone $base)->where('clicked', true)->count();
        $conversion = $searches > 0 ? round(($clicks / $searches) * 100, 1) : 0.0;

        $dailyRows = SearchEvent::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $trend = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $row = $dailyRows->get($cursor->format('Y-m-d'));
            $trend[] = ['label' => $cursor->format('M j'), 'value' => (int) ($row->aggregate ?? 0)];
            $cursor->addDay();
        }

        $top = SearchEvent::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('query, COUNT(*) as searches, AVG(result_count) as avg_results, SUM(CASE WHEN clicked = 1 THEN 1 ELSE 0 END) as clicks')
            ->groupBy('query')
            ->orderByDesc('searches')
            ->limit(12)
            ->get();

        return [
            'kpis' => [
                $this->kpi('Tracked Searches', $searches, 'search', null),
                $this->kpi('Unique Queries', $uniqueQueries, 'text-search', null),
                $this->kpi('Zero-result Searches', $zeroResults, 'circle-x', null),
                $this->kpi('Search Conversion', $conversion, 'mouse-pointer-click', null, 1, number_format($conversion, 1) . '%'),
            ],
            'chart' => ['title' => 'Search Activity', 'series_label' => 'Searches', 'points' => $trend],
            'table' => [
                'title' => 'Top Search Queries',
                'headers' => ['Query', 'Searches', 'Avg. Results', 'Conversion'],
                'rows' => $top->map(function ($row) {
                    $searchCount = max(1, (int) $row->searches);
                    return [
                        'query' => $row->query,
                        'searches' => number_format((int) $row->searches),
                        'results' => number_format((float) $row->avg_results, 1),
                        'conversion' => number_format(((int) $row->clicks / $searchCount) * 100, 1) . '%',
                    ];
                })->all(),
            ],
            'readiness' => [
                'level' => 'good',
                'title' => 'Search analytics connected',
                'message' => 'Public search queries, zero-result searches and result-click conversions are calculated from real search_events records.',
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

    private function visitorAnalyticsReady(): bool
    {
        return Schema::hasTable('analytics_visitors')
            && Schema::hasTable('analytics_sessions')
            && Schema::hasTable('analytics_page_views');
    }

    private function visitorTrend(Carbon $from, Carbon $to): array
    {
        $rows = AnalyticsPageView::query()
            ->whereBetween('viewed_at', [$from, $to])
            ->selectRaw('DATE(viewed_at) as day, COUNT(*) as views, COUNT(DISTINCT visitor_id) as visitors')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $views = [];
        $visitors = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $row = $rows->get($key);
            $label = $cursor->format('M j');
            $views[] = ['label' => $label, 'value' => (int) ($row->views ?? 0)];
            $visitors[] = ['label' => $label, 'value' => (int) ($row->visitors ?? 0)];
            $cursor->addDay();
        }

        return [$views, $visitors];
    }

    private function breakdownRow(string $label, int $value, int $total): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'share' => $total > 0 ? round(($value / $total) * 100, 1) : 0.0,
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
