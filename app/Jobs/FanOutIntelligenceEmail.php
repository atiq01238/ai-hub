<?php

namespace App\Jobs;

use App\Models\AiModel;
use App\Models\BenchmarkResult;
use App\Models\Company;
use App\Models\NewsItem;
use App\Models\PricingHistory;
use App\Models\Tool;
use App\Services\EmailIntelligenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FanOutIntelligenceEmail implements ShouldQueue
{
    use Queueable;

    public $afterCommit = true;
    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(
        public string $event,
        public int $subjectId,
        public ?string $subjectType = null,
        public ?string $followEvent = null,
    ) {}

    public function handle(EmailIntelligenceService $email): void
    {
        match ($this->event) {
            'tool_published' => $this->withModel(Tool::class, fn ($m) => $email->toolPublished($m)),
            'model_released' => $this->withModel(AiModel::class, fn ($m) => $email->modelReleased($m)),
            'breaking_news' => $this->withModel(NewsItem::class, fn ($m) => $email->breakingNewsPublished($m)),
            'pricing_change' => $this->withModel(PricingHistory::class, fn ($m) => $email->pricingChanged($m)),
            'benchmark_update' => $this->withModel(BenchmarkResult::class, fn ($m) => $email->benchmarkUpdated($m)),
            'followed_update' => $this->followed($email),
            default => null,
        };
    }

    private function followed(EmailIntelligenceService $email): void
    {
        $class = match ($this->subjectType) {
            'tool' => Tool::class,
            'model' => AiModel::class,
            'company' => Company::class,
            default => null,
        };
        if (! $class || ! $this->followEvent) return;
        $this->withModel($class, fn ($model) => $email->followedEntityUpdate($model, $this->subjectType, $this->followEvent));
    }

    private function withModel(string $class, callable $callback): void
    {
        $model = $class::find($this->subjectId);
        if ($model) $callback($model);
    }
}
