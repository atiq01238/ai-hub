<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'ai_models';

    protected $fillable = [
        'company_id', 'tool_id', 'name', 'slug', 'logo_path', 'cover_image_path', 'version', 'release_date',
        'context_window', 'input_price_per_million', 'output_price_per_million',
        'capabilities', 'capability_notes', 'benchmark_score', 'benchmarks', 'status',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'benchmarks'   => 'array',
        'release_date' => 'date',
        'input_price_per_million'  => 'float',
        'output_price_per_million' => 'float',
        'benchmark_score'          => 'float',
    ];


    public function reviews()
    {
        return $this->hasMany(Review::class, 'model_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }
    public function benchmarkResults()
    {
        return $this->morphMany(BenchmarkResult::class, 'benchmarkable');
    }


    public function testResults()
    {
        return $this->hasMany(AiTestResult::class, 'ai_model_id');
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path);
        return $this->company?->logo_url ?: asset('favicon.ico');
    }
    public function pricingSources()
    {
        return $this->hasMany(ModelPricingSource::class, 'ai_model_id');
    }

    public function pricingHistory()
    {
        return $this->hasMany(ModelPricingHistory::class, 'ai_model_id');
    }


}
