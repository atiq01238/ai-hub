<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Comparison;
use App\Models\Company;
use App\Models\AiModel;
use App\Models\NewsItem;
use App\Models\PricingHistory;
use App\Models\Review;
use App\Models\Submission;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $kpis = [
            'tools'       => Tool::count(),
            'models'      => AiModel::count(),
            'companies'   => Company::count(),
            'comparisons' => Comparison::count(),
            'news24h'     => NewsItem::where('created_at', '>=', now()->subDay())->count(),
            'reviews'     => Review::count(),
            'users'       => User::count(),
            'articles'    => Article::where('status', 'published')->count(),
        ];

        // Real content-growth chart for the last 30 days (Tools + Articles + News
        // added per day) — replaces the old fake "website views" chart, since we
        // don't track visitor analytics yet.
        $days = collect(range(29, 0))->map(fn ($i) => Carbon::today()->subDays($i));
        $chart = [
            'labels' => $days->map(fn ($d) => $d->format('M j'))->all(),
            'tools'  => $days->map(fn ($d) => Tool::whereDate('created_at', $d)->count())->all(),
            'news'   => $days->map(fn ($d) => NewsItem::whereDate('created_at', $d)->count())->all(),
            'articles' => $days->map(fn ($d) => Article::whereDate('created_at', $d)->count())->all(),
        ];

        $topRatedTools = Tool::with('company')
            ->where('status', 'published')
            ->orderByDesc('rating')
            ->take(5)
            ->get();

        $latestModels = AiModel::with('company')->latest()->take(4)->get();

        $recentNews = NewsItem::where('status', 'published')->latest('published_at')->take(4)->get();

        $recentTools = Tool::with('company')->latest()->take(4)->get();

        $priceChanges = PricingHistory::with('tool')->latest()->take(4)->get();

        $pending = [
            'reviews'     => Review::where('status', 'pending')->count(),
            'submissions' => Submission::where('status', 'pending')->count(),
        ];

        return view('dashboard.index', compact(
            'kpis', 'chart', 'topRatedTools', 'latestModels',
            'recentNews', 'recentTools', 'priceChanges', 'pending'
        ));
    }
}
