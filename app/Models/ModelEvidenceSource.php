<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelEvidenceSource extends Model
{
    protected $fillable = [
        'ai_model_id',
        'evidence_type',
        'source_name',
        'source_url',
        'source_type',
        'verification_status',
        'verified_at',
        'notes',
        'metadata',
        'source_hash',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function model()
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }
}
