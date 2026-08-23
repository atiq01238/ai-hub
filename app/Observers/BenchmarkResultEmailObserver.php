<?php

namespace App\Observers;

use App\Jobs\FanOutIntelligenceEmail;
use App\Models\BenchmarkResult;

class BenchmarkResultEmailObserver
{
    public function created(BenchmarkResult $result): void
    {
        if ($result->verified) FanOutIntelligenceEmail::dispatch('benchmark_update', $result->id);
    }

    public function updated(BenchmarkResult $result): void
    {
        if ($result->wasChanged('verified') && $result->verified) FanOutIntelligenceEmail::dispatch('benchmark_update', $result->id);
    }
}
