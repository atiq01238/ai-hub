<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| AI Hub News Automation
|--------------------------------------------------------------------------
|
| The server only needs Laravel's scheduler trigger. The actual news
| frequency and ON/OFF state are controlled from the Automation Monitor.
|
*/

$automationEnabled = true;
$frequencyMinutes = 60;

try {
    if (Schema::hasTable('settings')) {
        $automationEnabled = Setting::get('ai_news_automation_enabled', '1') === '1';
        $frequencyMinutes = (int) Setting::get('ai_news_frequency_minutes', '60');
    }
} catch (\Throwable $e) {
    // During install/migrate the DB may not be available yet. Keep safe defaults.
}

if ($automationEnabled) {
    $pipeline = Schedule::command('news:pipeline --limit=100')
        ->withoutOverlapping(55)
        ->appendOutputTo(storage_path('logs/news-pipeline.log'));

    match ($frequencyMinutes) {
        15 => $pipeline->everyFifteenMinutes(),
        30 => $pipeline->everyThirtyMinutes(),
        180 => $pipeline->everyThreeHours(),
        360 => $pipeline->everySixHours(),
        default => $pipeline->hourly(),
    };
}

Schedule::command('news:health-check')
    ->everySixHours()
    ->withoutOverlapping(20)
    ->appendOutputTo(storage_path('logs/news-health.log'));

Schedule::command('discovery:scan --limit=500')
    ->hourly()
    ->withoutOverlapping(30)
    ->appendOutputTo(storage_path('logs/ai-discovery.log'));

Schedule::command('discovery:health-check --notify')
    ->everySixHours()
    ->withoutOverlapping(20)
    ->appendOutputTo(storage_path('logs/ai-discovery-health.log'));

/* Content publishing: approved scheduled articles become public when due. */
Schedule::command('content:publish-scheduled')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->appendOutputTo(storage_path('logs/content-publishing.log'));


/* Email intelligence: weekly subscriber digest. */
Schedule::command('email:weekly-digest')
    ->sundays()->at('09:00')
    ->withoutOverlapping(30)
    ->appendOutputTo(storage_path('logs/email-weekly-digest.log'));
