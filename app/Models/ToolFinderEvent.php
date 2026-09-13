<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolFinderEvent extends Model
{
    protected $fillable = [
        'user_id',
        'task',
        'shortcut',
        'budget',
        'experience',
        'priority',
        'filters',
        'detected_use_cases',
        'detected_preferences',
        'result_count',
        'top_tool_id',
        'clicked_tool_id',
        'clicked_action',
        'session_key',
    ];

    protected $casts = [
        'filters' => 'array',
        'detected_use_cases' => 'array',
        'detected_preferences' => 'array',
        'result_count' => 'integer',
    ];
}
