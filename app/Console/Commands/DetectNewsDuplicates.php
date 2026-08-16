<?php

namespace App\Console\Commands;

use App\Services\Pricing\PricingDetectionService;
use Illuminate\Console\Command;

class DetectPricingChanges extends Command
{
    protected $signature = 'pricing:detect {--source= : Check one pricing source ID} {--plan= : Check all sources for one pricing plan ID}';

    protected $description = 'Check configured official pricing sources and create reviewable pending price changes';

    public function handle(PricingDetectionService $service): int
    {
        $stats = $service->scan(
            $this->option('source') ? (int) $this->option('source') : null,
            $this->option('plan') ? (int) $this->option('plan') : null,
        );

        $this->info("Checked {$stats['checked']} source(s): {$stats['changes']} change(s), {$stats['unchanged']} unchanged, {$stats['failed']} failed.");

        return self::SUCCESS;
    }
}
