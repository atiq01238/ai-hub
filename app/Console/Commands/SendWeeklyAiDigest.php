<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\NewsItem;
use App\Models\Tool;
use App\Models\User;
use App\Services\EmailIntelligenceService;
use Illuminate\Console\Command;

class SendWeeklyAiDigest extends Command
{
    protected $signature = 'email:weekly-digest {--user= : Send only to one user ID for testing}';
    protected $description = 'Queue the weekly AI Hub intelligence digest for subscribed verified users.';

    public function handle(EmailIntelligenceService $email): int
    {
        $since = now()->subDays(7);
        $lines = [];
        NewsItem::where('status','published')->where('published_at','>=',$since)->whereNull('duplicate_of_id')
            ->orderByDesc('importance')->limit(3)->pluck('headline')->each(fn($v)=>$lines[]='News: '.$v);
        AiModel::where('status','active')->where('created_at','>=',$since)->latest()->limit(3)->pluck('name')->each(fn($v)=>$lines[]='New model: '.$v);
        Tool::where('status','published')->where('published_at','>=',$since)->latest('published_at')->limit(3)->pluck('name')->each(fn($v)=>$lines[]='New tool: '.$v);
        if (! $lines) $lines[] = 'No major catalog release this week — your AI Hub watchlist remains up to date.';
        $digest = ['lines'=>array_slice($lines,0,8)];
        $weekKey = now()->startOfWeek()->toDateString();

        $query = User::whereNotNull('email_verified_at')->where('status','active')
            ->whereHas('emailPreference',fn($p)=>$p->where('email_enabled',true)->where('weekly_digest',true));
        if ($id = $this->option('user')) $query->whereKey((int)$id);
        $count = 0;
        $query->chunkById(200, function($users) use ($email,$digest,$weekKey,&$count): void {
            foreach ($users as $user) { $email->queueWeeklyDigest($user,$digest,$weekKey); $count++; }
        });
        $this->info("Weekly digest queued for {$count} user(s).");
        return self::SUCCESS;
    }
}
