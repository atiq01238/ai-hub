<?php

namespace App\Jobs;

use App\Models\PricingSource;
use App\Services\Pricing\PricingDetectionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckPricingSource implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 45;
    public function __construct(public int $sourceId)
    {
    }

    public function handle(PricingDetectionService $service): void
    {
        if (! PricingSource::query()->whereKey($this->sourceId)->where('enabled', true)->exists()) {
            return;
        }

        $service->scan($this->sourceId);
    }
}
