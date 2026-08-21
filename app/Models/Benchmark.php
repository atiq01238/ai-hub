<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Benchmark extends Model
{
    protected $fillable = ['name','slug','category','entity_scope','metric_type','unit','min_score','description','weight','max_score','version','variant','higher_is_better','official_url','methodology_url','is_active'];
    protected $casts = ['weight'=>'float','min_score'=>'float','max_score'=>'float','higher_is_better'=>'boolean','is_active'=>'boolean'];

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
