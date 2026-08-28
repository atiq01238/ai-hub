<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsPageView;
use App\Models\SearchEvent;
use App\Models\Tool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ToolTrendingService
{
    /**
     * Rank published tools from AI Orbit's own rolling 30-day engagement.
     *
     * Signals:
     * - 1 point  = unique tool profile visitor
     * - 3 points = click from an AI Orbit search result/autocomplete
     * - 2 points = AI Orbit search query that mentions the tool by name
     *
     * The visible movement compares the current rolling 30 days with the
     * preceding 30 days. No external API or fabricated percentage is used.
     */
    public function homepage(int $limit = 6): Collection
    {
        $tools = Tool::query()
            ->where('status', 'published')
            ->get();

        if ($tools->isEmpty()) {
            return collect();
        }

        $now = now();
        $currentFrom = $now->copy()->subDays(30);
        $previousFrom = $now->copy()->subDays(60);

        $viewRows = collect();
        if (Schema::hasTable('analytics_page_views')) {
            $viewRows = AnalyticsPageView::query()
                ->where('entity_type', 'tool')
                ->whereNotNull('entity_id')
                ->whereBetween('viewed_at', [$previousFrom, $now])
                ->selectRaw(
                    'entity_id,
                     COUNT(DISTINCT CASE WHEN viewed_at >= ? THEN visitor_id END) as current_views,
                     COUNT(DISTINCT CASE WHEN viewed_at < ? THEN visitor_id END) as previous_views',
                    [$currentFrom, $currentFrom]
                )
                ->groupBy('entity_id')
                ->get()
                ->keyBy('entity_id');
        }

        $clickRows = collect();
        $searchRows = collect();

        if (Schema::hasTable('search_events')) {
            $clickRows = SearchEvent::query()
                ->where('clicked', true)
                ->where('clicked_type', 'tool')
                ->whereNotNull('clicked_id')
                ->whereBetween('created_at', [$previousFrom, $now])
                ->selectRaw(
                    'clicked_id,
                     SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as current_clicks,
                     SUM(CASE WHEN created_at < ? THEN 1 ELSE 0 END) as previous_clicks',
                    [$currentFrom, $currentFrom]
                )
                ->groupBy('clicked_id')
                ->get()
                ->keyBy('clicked_id');

            // Group first so a busy site does not load every individual search row.
            $searchRows = SearchEvent::query()
                ->whereBetween('created_at', [$previousFrom, $now])
                ->selectRaw(
                    'query,
                     SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as current_searches,
                     SUM(CASE WHEN created_at < ? THEN 1 ELSE 0 END) as previous_searches',
                    [$currentFrom, $currentFrom]
                )
                ->groupBy('query')
                ->get()
                ->map(function ($row) {
                    $row->normalized_query = $this->normalizeWords((string) $row->query);
                    return $row;
                })
                ->filter(fn ($row) => $row->normalized_query !== '')
                ->values();
        }

        $hasPreviousActivity = $viewRows->contains(fn ($row) => (int) ($row->previous_views ?? 0) > 0)
            || $clickRows->contains(fn ($row) => (int) ($row->previous_clicks ?? 0) > 0)
            || $searchRows->contains(fn ($row) => (int) ($row->previous_searches ?? 0) > 0);

        $ranked = $tools->map(function (Tool $tool) use ($viewRows, $clickRows, $searchRows, $hasPreviousActivity) {
            $view = $viewRows->get($tool->id);
            $click = $clickRows->get($tool->id);

            $currentViews = (int) ($view->current_views ?? 0);
            $previousViews = (int) ($view->previous_views ?? 0);
            $currentClicks = (int) ($click->current_clicks ?? 0);
            $previousClicks = (int) ($click->previous_clicks ?? 0);

            [$currentSearches, $previousSearches] = $this->toolSearchDemand($tool, $searchRows);

            // Search clicks are the strongest intent signal, followed by direct
            // tool searches. A profile view is still the broadest activity signal.
            $currentScore = $currentViews + ($currentClicks * 3) + ($currentSearches * 2);
            $previousScore = $previousViews + ($previousClicks * 3) + ($previousSearches * 2);

            if ($previousScore > 0) {
                $change = (($currentScore - $previousScore) / $previousScore) * 100;

                if (abs($change) < 0.5) {
                    $label = '• 0%';
                } else {
                    $label = ($change > 0 ? '↑ ' : '↓ ').number_format(abs($change), 0).'%';
                }
            } elseif ($currentScore > 0) {
                $change = 100.0;
                $activityCount = $currentViews + $currentClicks + $currentSearches;
                // During the first month of analytics there is no honest prior
                // baseline to compare against, so show the measured 30-day
                // activity count instead of making every tool look "New".
                $label = $hasPreviousActivity ? '↑ New' : '30d '.number_format($activityCount);
            } else {
                $change = 0.0;
                $label = '—';
            }

            $tool->trend_current_views = $currentViews;
            $tool->trend_previous_views = $previousViews;
            $tool->trend_current_clicks = $currentClicks;
            $tool->trend_previous_clicks = $previousClicks;
            $tool->trend_current_searches = $currentSearches;
            $tool->trend_previous_searches = $previousSearches;

            // Home's fire badge is deliberately based on click momentum rather
            // than the general weighted trend score. Two or more recent search
            // result clicks are required so a single accidental click cannot
            // make a tool look hot.
            $clickGain = $currentClicks - $previousClicks;
            $tool->trend_is_hot = $currentClicks >= 2
                && $clickGain > 0
                && ($previousClicks === 0 || $currentClicks >= (int) ceil($previousClicks * 1.25));

            $tool->trend_current_score = $currentScore;
            $tool->trend_previous_score = $previousScore;
            $tool->trend_change = $change;
            $tool->trend_label = $label;
            $tool->trend_details = sprintf(
                'Last 30 days: %d unique views · %d search clicks · %d tool searches',
                $currentViews,
                $currentClicks,
                $currentSearches
            );

            return $tool;
        })
            ->filter(fn (Tool $tool) => $tool->trend_current_score > 0)
            ->sort(function (Tool $a, Tool $b) {
                $scoreCompare = $b->trend_current_score <=> $a->trend_current_score;
                if ($scoreCompare !== 0) {
                    return $scoreCompare;
                }

                $changeCompare = $b->trend_change <=> $a->trend_change;
                if ($changeCompare !== 0) {
                    return $changeCompare;
                }

                return ((float) $b->rating) <=> ((float) $a->rating);
            })
            ->take($limit)
            ->values();

        return $ranked->isNotEmpty() ? $ranked : $this->fallback($limit);
    }

    /**
     * Count search demand for a tool across grouped search queries.
     */
    private function toolSearchDemand(Tool $tool, Collection $searchRows): array
    {
        if ($searchRows->isEmpty()) {
            return [0, 0];
        }

        $needles = collect([$tool->name, $tool->slug])
            ->map(fn ($value) => $this->normalizeWords((string) $value))
            ->filter(fn ($value) => mb_strlen(str_replace(' ', '', $value)) >= 3)
            ->unique()
            ->values();

        if ($needles->isEmpty()) {
            return [0, 0];
        }

        $current = 0;
        $previous = 0;

        foreach ($searchRows as $row) {
            $query = ' '.$row->normalized_query.' ';
            $mentionsTool = $needles->contains(
                fn ($needle) => str_contains($query, ' '.$needle.' ')
            );

            if (! $mentionsTool) {
                continue;
            }

            $current += (int) ($row->current_searches ?? 0);
            $previous += (int) ($row->previous_searches ?? 0);
        }

        return [$current, $previous];
    }

    private function normalizeWords(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function fallback(int $limit): Collection
    {
        return Tool::query()
            ->where('status', 'published')
            ->orderByDesc('popularity')
            ->orderByDesc('rating')
            ->take($limit)
            ->get()
            ->each(function (Tool $tool) {
                // Until AI Orbit has measurable first-party activity, do not
                // manufacture a percentage from the static popularity field.
                $tool->trend_current_views = 0;
                $tool->trend_previous_views = 0;
                $tool->trend_current_clicks = 0;
                $tool->trend_previous_clicks = 0;
                $tool->trend_current_searches = 0;
                $tool->trend_previous_searches = 0;
                $tool->trend_is_hot = false;
                $tool->trend_current_score = 0;
                $tool->trend_previous_score = 0;
                $tool->trend_change = 0.0;
                $tool->trend_label = '—';
                $tool->trend_details = 'Not enough first-party activity in the last 30 days yet.';
            });
    }
}
