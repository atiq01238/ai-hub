<?php

namespace App\Observers;

use App\Jobs\FanOutIntelligenceEmail;
use App\Models\PricingHistory;

class PricingHistoryEmailObserver
{
    public function created(PricingHistory $history): void
    {
        FanOutIntelligenceEmail::dispatch('pricing_change', $history->id);
    }
}
