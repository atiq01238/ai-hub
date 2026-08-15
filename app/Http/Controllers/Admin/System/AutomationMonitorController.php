<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\NewsItem;
use App\Models\NewsSource;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AutomationMonitorController extends Controller
{
    private const FREQUENCIES = [15, 30, 60, 180, 360];

    public function index()
    {
        $hasNewsSources = Schema::hasTable('news_sources');
        $hasNewsItems = Schema::hasTable('news_items');
        $hasSettings = Schema::hasTable('settings');

        $sources = $hasNewsSources
            ? NewsSource::query()
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->get()
            : collect();

        $activeSources = $sources->where('status', 'active')->count();
        $inactiveSources = $sources->where('status', 'inactive')->count();

        $newsToday = 0;
        $publishedToday = 0;

        if ($hasNewsItems) {
            $dateColumn = Schema::hasColumn('news_items', 'published_at')
                ? 'published_at'
                : 'created_at';

            $newsToday = NewsItem::query()
                ->whereDate($dateColumn, now()->toDateString())
                ->count();

            if (Schema::hasColumn('news_items', 'status')) {
                $publishedToday = NewsItem::query()
                    ->where('status', 'published')
                    ->whereDate($dateColumn, now()->toDateString())
                    ->count();
            } elseif (Schema::hasColumn('news_items', 'is_published')) {
                $publishedToday = NewsItem::query()
                    ->where('is_published', true)
                    ->whereDate($dateColumn, now()->toDateString())
                    ->count();
            }
        }

        $failedSources = 0;
        if ($hasNewsSources) {
            if (Schema::hasColumn('news_sources', 'consecutive_failures')) {
                $failedSources = NewsSource::query()
                    ->where('consecutive_failures', '>', 0)
                    ->count();
            } elseif (Schema::hasColumn('news_sources', 'last_error')) {
                $failedSources = NewsSource::query()
                    ->whereNotNull('last_error')
                    ->where('last_error', '!=', '')
                    ->count();
            }
        }

        $lastFetch = null;
        if ($hasNewsSources) {
            if (Schema::hasColumn('news_sources', 'last_fetched_at')) {
                $lastFetch = NewsSource::query()->whereNotNull('last_fetched_at')->max('last_fetched_at');
            } elseif (Schema::hasColumn('news_sources', 'last_success_at')) {
                $lastFetch = NewsSource::query()->whereNotNull('last_success_at')->max('last_success_at');
            }
        }

        $processing = [
            'available' => false,
            'pending' => 0,
            'processing' => 0,
            'processed' => 0,
            'failed' => 0,
        ];

        if ($hasNewsItems && Schema::hasColumn('news_items', 'processing_status')) {
            $processing['available'] = true;
            $processing['pending'] = NewsItem::query()->whereIn('processing_status', ['pending', 'queued'])->count();
            $processing['processing'] = NewsItem::query()->where('processing_status', 'processing')->count();
            $processing['processed'] = NewsItem::query()->where('processing_status', 'processed')->count();
            $processing['failed'] = NewsItem::query()->where('processing_status', 'failed')->count();
        }

        $logs = Schema::hasTable('ai_processing_logs')
            ? DB::table('ai_processing_logs')->latest('id')->limit(20)->get()
            : collect();

        $automationEnabled = $hasSettings
            ? Setting::get('ai_news_automation_enabled', '1') === '1'
            : true;

        $frequencyMinutes = $hasSettings
            ? (int) Setting::get('ai_news_frequency_minutes', '60')
            : 60;

        if (! in_array($frequencyMinutes, self::FREQUENCIES, true)) {
            $frequencyMinutes = 60;
        }

        $lastRunStartedAt = $hasSettings ? Setting::get('ai_news_last_run_started_at') : null;
        $lastRunFinishedAt = $hasSettings ? Setting::get('ai_news_last_run_finished_at') : null;
        $lastRunStatus = $hasSettings ? Setting::get('ai_news_last_run_status', 'never') : 'never';
        $lastRunDuration = $hasSettings ? Setting::get('ai_news_last_run_duration_seconds') : null;
        $lastRunMessage = $hasSettings ? Setting::get('ai_news_last_run_message') : null;

        $nextRunAt = $automationEnabled
            ? $this->nextScheduledRun($frequencyMinutes)
            : null;

        $frequencyOptions = [
            15 => 'Every 15 Minutes',
            30 => 'Every 30 Minutes',
            60 => 'Every 1 Hour',
            180 => 'Every 3 Hours',
            360 => 'Every 6 Hours',
        ];

        $stats = [
            'total_sources' => $sources->count(),
            'active_sources' => $activeSources,
            'inactive_sources' => $inactiveSources,
            'total_articles' => $hasNewsItems ? NewsItem::query()->count() : 0,
            'today_articles' => $newsToday,
            'published_today' => $publishedToday,
            'failed_sources' => $failedSources,
        ];

        return view('system.automation-monitor', compact(
            'sources',
            'activeSources',
            'inactiveSources',
            'newsToday',
            'publishedToday',
            'failedSources',
            'lastFetch',
            'processing',
            'logs',
            'stats',
            'automationEnabled',
            'frequencyMinutes',
            'frequencyOptions',
            'lastRunStartedAt',
            'lastRunFinishedAt',
            'lastRunStatus',
            'lastRunDuration',
            'lastRunMessage',
            'nextRunAt'
        ));
    }

    public function update(Request $request)
    {
        if (! Schema::hasTable('settings')) {
            return back()->with('error', 'Settings table is missing. Run php artisan migrate first.');
        }

        $validated = $request->validate([
            'automation_enabled' => ['required', Rule::in(['0', '1'])],
            'frequency_minutes' => ['required', 'integer', Rule::in(self::FREQUENCIES)],
        ]);

        Setting::set('ai_news_automation_enabled', (string) $validated['automation_enabled']);
        Setting::set('ai_news_frequency_minutes', (string) $validated['frequency_minutes']);

        return back()->with(
            'status',
            $validated['automation_enabled'] === '1'
                ? 'News automation enabled and schedule updated.'
                : 'News automation paused. Manual pipeline runs are still available.'
        );
    }

    public function runNow()
    {
        $exitCode = Artisan::call('news:pipeline', ['--limit' => 100]);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return back()->with('error', $output ?: 'News pipeline failed. Check storage/logs/news-pipeline.log.');
        }

        return back()->with('status', $output ?: 'News pipeline completed successfully.');
    }

    private function nextScheduledRun(int $minutes)
    {
        $next = now()->copy()->startOfMinute();

        if ($minutes === 15 || $minutes === 30) {
            $remainder = $next->minute % $minutes;
            $add = $remainder === 0 && now()->second === 0 ? $minutes : ($minutes - $remainder);
            return $next->addMinutes($add);
        }

        if ($minutes === 60) {
            return $next->startOfHour()->addHour();
        }

        $hours = intdiv($minutes, 60);
        $candidate = $next->startOfHour()->addHour();

        while (($candidate->hour % $hours) !== 0) {
            $candidate->addHour();
        }

        return $candidate;
    }
}
