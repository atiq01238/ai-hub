<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolFactEvidence extends Model
{
    protected $table = 'tool_fact_evidence';

    protected $fillable = [
        'tool_id', 'tool_source_id', 'fact_type', 'fact_key',
        'verification_status', 'verified_at', 'notes',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function source()
    {
        return $this->belongsTo(ToolSource::class, 'tool_source_id');
    }
}
