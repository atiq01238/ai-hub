<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\AppNotification;
use App\Models\BenchmarkResult;
use App\Models\EmailDeliveryLog;
use App\Models\EmailPreference;
use App\Models\NewsItem;
use App\Models\PricingHistory;
use App\Models\Tool;
use App\Models\User;
use App\Models\UserInteraction;
use App\Notifications\IntelligenceEmailAlert;
use App\Notifications\WelcomeToAiHubNotification;
use App\Notifications\WeeklyAiDigestNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class EmailIntelligenceService
{
    public function ensurePreferences(User $user): EmailPreference
    {
        return EmailPreference::firstOrCreate(['user_id' => $user->id], EmailPreference::defaults());
    }

    public function queueWelcome(User $user): void
    {
        if (! $user->email_verified_at || ($user->status ?? 'active') !== 'active') return;
        $this->ensurePreferences($user);
        $this->queueForUser($user, 'welcome', 'welcome:'.$user->id, 'Welcome to AI Orbit',
            fn ($log) => new WelcomeToAiHubNotification($log->id));
    }

    public function toolPublished(Tool $tool): void
    {
        $tool->loadMissing('company');
        $this->broadcast(
            'new_tools', 'new_tool:'.$tool->id, 'New AI Tool: '.$tool->name,
            'New AI tool on AI Orbit', $tool->name.' is now available on AI Orbit. Explore its capabilities, pricing, reviews, and related models.',
            'View Tool', route('tools.show', $tool), 'sparkles', 'pos', 'new_tool',
            [['type'=>'company','id'=>$tool->company_id,'alert'=>'major_update']]
        );
    }

    public function modelReleased(AiModel $model): void
    {
        $model->loadMissing('company');
        $this->broadcast(
            'new_models', 'new_model:'.$model->id, 'New AI Model: '.$model->name,
            'New AI model on AI Orbit', $model->name.' is now active on AI Orbit. See its capabilities, context window, pricing, benchmarks, and comparisons.',
            'View Model', route('models.show', $model), 'brain-circuit', 'pos', 'new_model',
            [['type'=>'company','id'=>$model->company_id,'alert'=>'major_update']]
        );
    }

    public function breakingNewsPublished(NewsItem $news): void
    {
        if (! $this->isBreaking($news) || $news->duplicate_status === 'duplicate') return;
        $this->broadcast(
            'breaking_news', 'breaking_news:'.$news->id, 'Breaking AI News: '.Str::limit($news->headline, 120, '…'),
            'Breaking AI news', $news->ai_summary ?: $news->summary ?: $news->headline,
            'Read the Story', route('news.show', $news), 'zap', 'warn', 'breaking_news',
            [['type'=>'company','id'=>$news->company_id,'alert'=>'news']]
        );
    }

    public function pricingChanged(PricingHistory $history): void
    {
        $history->loadMissing('tool.company');
        $tool = $history->tool;
        if (! $tool || $tool->status !== 'published') return;
        $metric = Str::headline($history->metric ?: 'pricing');
        $message = $tool->name.' pricing changed for '.$history->plan_name.' ('.$metric.'). Review the latest verified pricing intelligence on AI Orbit.';
        $this->broadcast(
            'price_changes', 'price_change:'.$history->id, 'Price update: '.$tool->name,
            'AI pricing update', $message, 'View Pricing', route('pricing.show', $tool), 'tag', 'warn', 'price_change',
            [['type'=>'tool','id'=>$tool->id,'alert'=>'pricing'], ['type'=>'company','id'=>$tool->company_id,'alert'=>'pricing']]
        );
    }

    public function benchmarkUpdated(BenchmarkResult $result): void
    {
        if (! $result->verified) return;
        $result->loadMissing(['benchmark', 'benchmarkable']);
        $target = $result->benchmarkable;
        if (! $target) return;
        $type = $target instanceof Tool ? 'tool' : ($target instanceof AiModel ? 'model' : null);
        if (! $type) return;
        $url = $type === 'tool' ? route('tools.show', $target) : route('models.show', $target);
        $this->broadcast(
            'benchmark_updates', 'benchmark_result:'.$result->id, 'Benchmark update: '.$target->name,
            'Verified benchmark update', $target->name.' has a new verified '.$result->benchmark?->name.' result on AI Orbit.',
            'View Benchmark Data', $url, 'bar-chart-3', 'info', 'benchmark_update',
            [['type'=>$type,'id'=>$target->id,'alert'=>'benchmark']]
        );
    }

    public function followedEntityUpdate(Model $model, string $type, string $event): void
    {
        if (! in_array($event, ['major_update','pricing','benchmark','news'], true)) return;
        $followers = $this->followerUserIds([['type'=>$type,'id'=>$model->getKey(),'alert'=>$event]]);
        if (! $followers) return;
        $name = $model->name ?? ucfirst($type);
        $url = match ($type) {
            'tool' => route('tools.show', $model),
            'model' => route('models.show', $model),
            'company' => route('companies.show', $model),
            default => route('home'),
        };
        $subject = $name.' was updated on AI Orbit';
        $this->usersQuery('followed_entities')->whereIn('id', $followers)->chunkById(200, function ($users) use ($model,$type,$event,$name,$url,$subject): void {
            foreach ($users as $user) {
                $key = 'follow_'.$event.':'.$type.':'.$model->getKey().':'.($model->updated_at?->timestamp ?? time());
                $this->queueAlert($user, 'followed_entities', $key, $subject, $name.' update',
                    'A '.$type.' you follow has a '.str_replace('_',' ',$event).' update on AI Orbit.', 'View Update', $url);
            }
        });
    }

    public function queueWeeklyDigest(User $user, array $digest, string $weekKey): void
    {
        $pref = $this->ensurePreferences($user);
        if (! $pref->email_enabled || ! $pref->weekly_digest || ! $user->email_verified_at) return;
        $this->queueForUser($user, 'weekly_digest', 'weekly_digest:'.$weekKey, 'Your weekly AI Orbit intelligence digest',
            fn ($log) => new WeeklyAiDigestNotification($digest, $log->id, $this->unsubscribeUrl($user)));
    }

    private function broadcast(string $preference, string $eventKey, string $subject, string $heading, string $message, string $actionLabel, string $url, string $icon, string $tone, string $type, array $followTargets = []): void
    {
        $followerIds = $this->followerUserIds($followTargets);
        $query = User::query()
            ->whereNotNull('email_verified_at')
            ->where('status', 'active')
            ->where(function ($q) { $q->whereNull('role')->orWhere('role', '!=', 'admin'); })
            ->where(function ($q) use ($preference, $followerIds) {
                $q->whereHas('emailPreference', fn ($p) => $p->where('email_enabled', true)->where($preference, true));
                if ($followerIds) {
                    $q->orWhere(function ($followerQuery) use ($followerIds) {
                        $followerQuery->whereIn('users.id', $followerIds)
                            ->whereHas('emailPreference', fn ($p) => $p->where('email_enabled', true)->where('followed_entities', true));
                    });
                }
            });

        $query->distinct()->chunkById(200, function ($users) use ($preference,$eventKey,$subject,$heading,$message,$actionLabel,$url,$icon,$tone,$type): void {
            foreach ($users as $user) {
                AppNotification::sendTo((int)$user->id, $icon, $tone, $heading, Str::limit(strip_tags($message), 250), $url, $type);
                $this->queueAlert($user, $preference, $eventKey, $subject, $heading, $message, $actionLabel, $url);
            }
        }, 'users.id', 'id');
    }

    private function queueAlert(User $user, string $category, string $eventKey, string $subject, string $heading, string $message, string $actionLabel, string $url): void
    {
        $this->queueForUser($user, $category, $eventKey, $subject,
            fn ($log) => new IntelligenceEmailAlert($subject, $heading, Str::limit(strip_tags($message), 700), $actionLabel, $url, $log->id, $this->unsubscribeUrl($user)));
    }

    private function queueForUser(User $user, string $category, string $eventKey, string $subject, callable $notificationFactory): void
    {
        try {
            $log = EmailDeliveryLog::firstOrCreate(
                ['user_id'=>$user->id, 'event_key'=>$eventKey],
                ['category'=>$category, 'subject'=>$subject, 'status'=>'queued', 'queued_at'=>now()]
            );
            if (! $log->wasRecentlyCreated) return;
            $notification = $notificationFactory($log);
            $log->update(['notification_class' => $notification::class]);
            $user->notify($notification);
        } catch (Throwable $e) {
            if (isset($log)) $log->update(['status'=>'failed','error'=>Str::limit($e->getMessage(),4000),'failed_at'=>now()]);
            report($e);
        }
    }

    private function usersQuery(string $preference)
    {
        return User::query()
            ->whereNotNull('email_verified_at')
            ->where('status', 'active')
            ->where(function ($q) { $q->whereNull('role')->orWhere('role', '!=', 'admin'); })
            ->whereHas('emailPreference', fn ($p) => $p->where('email_enabled', true)->where($preference, true));
    }

    private function followerUserIds(array $targets): array
    {
        $ids = [];
        foreach ($targets as $target) {
            if (empty($target['id'])) continue;
            UserInteraction::query()->where('action','follow')->where('target_type',$target['type'])->where('target_id',$target['id'])
                ->get(['user_id','metadata'])->each(function ($follow) use (&$ids,$target): void {
                    $alerts = $follow->metadata['alerts'] ?? ['news','pricing','benchmark','major_update'];
                    if (in_array($target['alert'], $alerts, true)) $ids[] = (int)$follow->user_id;
                });
        }
        return array_values(array_unique($ids));
    }

    private function isBreaking(NewsItem $news): bool
    {
        return $news->category === 'Breaking News' || (int)$news->importance >= 75;
    }

    private function unsubscribeUrl(User $user): string
    {
        return URL::temporarySignedRoute('email.unsubscribe', now()->addYears(5), ['user'=>$user->id]);
    }
}
