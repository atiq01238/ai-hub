<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $fillable = ['name', 'description', 'environment', 'rollout_percentage', 'is_enabled'];

    protected $casts = ['is_enabled' => 'boolean'];
}
