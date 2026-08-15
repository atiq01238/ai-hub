<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\NewsSource;
use Illuminate\Http\Request;

class SourceReliabilityController extends Controller
{
    public function index(Request $request)
    {
        $sources = NewsSource::query()
            ->withCount([
                'newsItems as total_articles',
                'newsItems as verified_articles' => fn ($q) => $q->where('verification_status', 'verified'),
                'newsItems as duplicate_articles' => fn ($q) => $q->where('duplicate_status', 'duplicate'),
                'newsItems as failed_articles' => fn ($q) => $q->where('processing_status', 'failed'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (NewsSource $source) {
                $total = max(1, (int) $source->total_articles);
                $verificationRate = (int) round(((int) $source->verified_articles / $total) * 100);
                $duplicateRate = (int) round(((int) $source->duplicate_articles / $total) * 100);
                $processingSuccess = (int) round((1 - ((int) $source->failed_articles / $total)) * 100);

                $healthScore = $source->status === 'active' ? 100 : 55;
                $healthScore -= min(45, ((int) $source->consecutive_failures) * 12);
                if ($source->last_error) {
                    $healthScore -= 8;
                }
                if ($source->last_success_at && $source->last_success_at->lt(now()->subDays(2))) {
                    $healthScore -= 10;
                }
                $healthScore = max(0, min(100, $healthScore));

                // Reliability is based only on measurable local signals.
                // Verification has a modest weight because an unverified item
                // is not the same thing as an inaccurate source.
                $score = (int) round(
                    ($healthScore * 0.45)
                    + ($processingSuccess * 0.30)
                    + ((100 - $duplicateRate) * 0.15)
                    + ($verificationRate * 0.10)
                );

                return (object) [
                    'id' => $source->id,
                    'name' => $source->name,
                    'status' => $source->status,
                    'score' => max(0, min(100, $score)),
                    'health' => $healthScore,
                    'verification_rate' => $verificationRate,
                    'duplicate_rate' => $duplicateRate,
                    'failed_reports' => (int) $source->failed_articles + (int) $source->consecutive_failures,
                    'total_articles' => (int) $source->total_articles,
                    'last_success_at' => $source->last_success_at,
                    'last_error' => $source->last_error,
                ];
            })
            ->sortByDesc('score')
            ->values();

        return view('system.source-reliability', compact('sources'));
    }
}
