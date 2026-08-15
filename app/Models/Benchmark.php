<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Benchmark extends Model
{
    protected $fillable = ['name','slug','category','description','weight','max_score','higher_is_better','official_url','is_active'];
    protected $casts = ['weight'=>'decimal:2','max_score'=>'decimal:2','higher_is_better'=>'boolean','is_active'=>'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Benchmark $benchmark) {
            $benchmark->slug ??= Str::slug($benchmark->name);
        });
    }

    public function results(): HasMany
    {
        return $this->hasMany(BenchmarkResult::class);
    }
}
