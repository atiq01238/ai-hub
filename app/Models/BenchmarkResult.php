<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BenchmarkResult extends Model
{
    protected $fillable = ['benchmark_id','benchmarkable_type','benchmarkable_id','score','tested_at','source_name','source_url','notes','verified'];
    protected $casts = ['score'=>'decimal:2','tested_at'=>'date','verified'=>'boolean'];

    public function benchmark(): BelongsTo { return $this->belongsTo(Benchmark::class); }
    public function benchmarkable(): MorphTo { return $this->morphTo(); }
}
