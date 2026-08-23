<?php

namespace App\Observers;

use App\Jobs\FanOutIntelligenceEmail;
use App\Models\AiModel;
use App\Models\NewsItem;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Model;

class EmailIntelligenceObserver
{
    public function created(Model $model): void
    {
        if ($model instanceof Tool && $model->status === 'published') FanOutIntelligenceEmail::dispatch('tool_published', $model->id);
        if ($model instanceof AiModel && $model->status === 'active') FanOutIntelligenceEmail::dispatch('model_released', $model->id);
        if ($model instanceof NewsItem && $model->status === 'published') FanOutIntelligenceEmail::dispatch('breaking_news', $model->id);
    }

    public function updated(Model $model): void
    {
        if ($model instanceof Tool && $model->wasChanged('status') && $model->status === 'published') FanOutIntelligenceEmail::dispatch('tool_published', $model->id);
        if ($model instanceof AiModel && $model->wasChanged('status') && $model->status === 'active') FanOutIntelligenceEmail::dispatch('model_released', $model->id);
        if ($model instanceof NewsItem && $model->status === 'published' && $model->wasChanged(['status','category','importance'])) FanOutIntelligenceEmail::dispatch('breaking_news', $model->id);
    }
}
