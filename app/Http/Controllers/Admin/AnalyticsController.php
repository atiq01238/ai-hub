<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Review;
use App\Models\SocialPost;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function website(Request $request) { return view('analytics.index'); }
    public function tools(Request $request) { return view('analytics.index'); }
    public function search(Request $request) { return view('analytics.index'); }
    public function comparisons(Request $request) { return view('analytics.index'); }

    public function content(Request $request)
    {
        $from = now()->subDays(29)->startOfDay();
        $contentMetrics = [
            'published_articles' => Article::where('status', 'published')->count(),
            'scheduled_articles' => Article::where('status', 'scheduled')->count(),
            'pending_reviews' => Review::where('status', 'pending')->count(),
            'published_reviews' => Review::where('status', 'published')->count(),
            'social_posts' => SocialPost::count(),
            'approval_queue' => Article::whereIn('approval_status', ['in_review', 'needs_changes'])->count(),
        ];
        $contentTrend = collect(range(0, 29))->map(function ($offset) use ($from) {
            $date = $from->copy()->addDays($offset);
            return [
                'label' => $date->format('M j'),
                'value' => Article::whereDate('published_at', $date)->where('status', 'published')->count(),
            ];
        });
        return view('analytics.index', compact('contentMetrics', 'contentTrend'));
    }

    public function trending(Request $request) { return view('analytics.index'); }
}
