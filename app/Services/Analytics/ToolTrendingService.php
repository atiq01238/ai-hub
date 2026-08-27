<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsPageView;
use App\Models\Tool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ToolTrendingService
{
    /**
     * Rank published tools by real AI Orbit unique-visitor profile-view momentum.
     *
     * The visible percentage compares the last 30 rolling days with
     * the preceding 30 days. No external API or fabricated percentage is used.
     */
    public function homepage(int $limit = 6): Collection
    {
        if (! Schema::hasTable('analytics_page_views')) {
            return $this->fallback($limit);
        }

        $now = now();
        $currentFrom = $now->copy()->subDays(30);
        $previousFrom = $now->copy()->subDays(60);

        $rows = AnalyticsPageView::query()
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

        if ($rows->isEmpty()) {
            return $this->fallback($limit);
        }

        $tools = Tool::query()
            ->where('status', 'published')
            ->whereIn('id', $rows->keys())
            ->get()
            ->map(function (Tool $tool) use ($rows) {
                $row = $rows->get($tool->id);
                $current = (int) ($row->current_views ?? 0);
                $previous = (int) ($row->previous_views ?? 0);

                if ($previous > 0) {
                    $change = (($current - $previous) / $previous) * 100;
                    $label = ($change >= 0 ? '↑ ' : '↓ ').number_format(abs($change), 0).'%';
                } elseif ($current > 0) {
                    $change = 100.0;
                    $label = '↑ New';
                } else {
                    $change = 0.0;
                    $label = '—';
                }

                // Current unique audience matters most; growth breaks ties and surfaces momentum.
                $tool->trend_current_views = $current;
                $tool->trend_previous_views = $previous;
                $tool->trend_change = $change;
                $tool->trend_label = $label;
                $tool->trend_score = ($current * 1000) + max(-999, min(999, $change));

                return $tool;
            })
            ->filter(fn (Tool $tool) => $tool->trend_current_views > 0)
            ->sortByDesc('trend_score')
            ->take($limit)
            ->values();

        return $tools->isNotEmpty() ? $tools : $this->fallback($limit);
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
                // Until AI Orbit has enough visitor history, never invent a trend percentage.
                $tool->trend_current_views = 0;
                $tool->trend_previous_views = 0;
                $tool->trend_change = 0.0;
                $tool->trend_label = '—';
            });
    }
}
