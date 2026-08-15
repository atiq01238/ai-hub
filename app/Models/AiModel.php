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
        'company_id', 'tool_id', 'name', 'slug', 'version', 'release_date',
        'context_window', 'input_price_per_million', 'output_price_per_million',
        'capabilities', 'capability_notes', 'benchmark_score', 'benchmarks', 'status',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'benchmarks'   => 'array',
        'release_date' => 'date',
    ];

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

}
