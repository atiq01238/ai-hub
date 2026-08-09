<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiTest extends Model
{
    protected $fillable = ['name', 'prompt', 'category', 'criteria', 'expected_output'];

    public function results()
    {
        return $this->hasMany(AiTestResult::class);
    }
}
