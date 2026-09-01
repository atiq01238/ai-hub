<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolSource extends Model
{
    public const TYPES = [
        'official_product', 'official_pricing', 'documentation', 'api_docs', 'changelog',
        'security', 'privacy', 'terms', 'repository', 'company', 'independent_review',
        'benchmark_source', 'integration_docs', 'compliance', 'license', 'availability', 'support',
    ];

    public const VERIFICATION_STATUSES = ['pending', 'verified', 'rejected', 'stale'];

    protected $fillable = [
        'tool_id', 'source_type', 'source_name', 'source_url', 'is_primary',
        'verification_status', 'last_checked_at', 'verified_at', 'verified_by',
        'notes', 'enabled',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'enabled' => 'boolean',
        'last_checked_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function factEvidence()
    {
        return $this->hasMany(ToolFactEvidence::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }
}
