<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BenchmarkResult extends Model
{
    protected $fillable = ['benchmark_id','benchmarkable_type','benchmarkable_id','score','model_version','tested_at','source_type','source_name','source_url','notes','status','verified','verified_by','verified_at','fingerprint'];
    protected $casts = ['score'=>'float','tested_at'=>'date','verified'=>'boolean','verified_at'=>'datetime'];

    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    public function benchmark(): BelongsTo { return $this->belongsTo(Benchmark::class); }
    public function benchmarkable(): MorphTo { return $this->morphTo(); }
}
