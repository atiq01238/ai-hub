<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\MediaUrl;

class AiModel extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'ai_models';

    protected $fillable = [
        'company_id', 'tool_id', 'name', 'slug', 'logo_path', 'cover_image_path', 'version', 'release_date',
        'context_window', 'input_price_per_million', 'output_price_per_million',
        'capabilities', 'capability_notes', 'benchmark_score', 'benchmarks', 'status',
        'official_source_url', 'official_name', 'official_model_id', 'identity_status', 'identity_notes', 'identity_verified_at',
        'profile_verification_status', 'profile_verified_at',
        'pricing_type', 'pricing_basis', 'pricing_unit_label', 'pricing_summary',
        'pricing_verification_status', 'pricing_verified_at',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'benchmarks'   => 'array',
        'release_date' => 'date',
        'input_price_per_million'  => 'float',
        'output_price_per_million' => 'float',
        'benchmark_score'          => 'float',
        'identity_verified_at'      => 'datetime',
        'profile_verified_at'       => 'datetime',
        'pricing_verified_at'       => 'datetime',
    ];


    public function reviews()
    {
        return $this->hasMany(Review::class, 'model_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function featureTerms()
    {
        return $this->belongsToMany(Feature::class, 'ai_model_feature')->withTimestamps();
    }

    public function useCaseTerms()
    {
        return $this->belongsToMany(UseCase::class, 'ai_model_use_case')->withTimestamps();
    }

    public function tagTerms()
    {
        return $this->belongsToMany(Tag::class, 'ai_model_tag')->withTimestamps();
    }
    public function benchmarkResults()
    {
        return $this->morphMany(BenchmarkResult::class, 'benchmarkable');
    }


    public function testResults()
    {
        return $this->hasMany(AiTestResult::class, 'ai_model_id');
    }

    public function getLogoUrlAttribute(): string
    {
        return MediaUrl::resolve($this->logo_path)
            ?: $this->tool?->logo_url
            ?: $this->company?->logo_url
            ?: MediaUrl::placeholder();
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return MediaUrl::resolve($this->cover_image_path) ?: $this->tool?->cover_image_url;
    }

    public function getOverviewAttribute(): string
    {
        $provider = $this->company?->name ?: 'its provider';
        $parts = [];

        if (filled($this->capability_notes)) {
            $parts[] = trim((string) $this->capability_notes);
        } else {
            $parts[] = $this->name . ' is an AI model from ' . $provider . '.';
        }

        $specs = [];
        if ($this->version) $specs[] = 'version ' . $this->version;
        if ($this->context_window) $specs[] = 'a ' . $this->context_window . ' context window';
        if ($this->release_date) $specs[] = 'released ' . $this->release_date->format('F Y');
        if ($specs) $parts[] = 'The current AI Orbit profile lists ' . implode(', ', $specs) . '.';

        $caps = collect($this->capabilities ?? [])->filter()->take(6)->values();
        if ($caps->isNotEmpty()) {
            $parts[] = 'Cataloged capabilities include ' . $caps->join(', ', ' and ') . '.';
        }

        if ($this->input_price_per_million !== null || $this->output_price_per_million !== null) {
            $pricing = [];
            if ($this->input_price_per_million !== null) $pricing[] = '$' . number_format((float) $this->input_price_per_million, 2) . ' input';
            if ($this->output_price_per_million !== null) $pricing[] = '$' . number_format((float) $this->output_price_per_million, 2) . ' output';
            $parts[] = 'Verified API pricing currently stored in AI Orbit is ' . implode(' and ', $pricing) . ' per 1M tokens.';
        } elseif (filled($this->pricing_basis) || filled($this->pricing_type)) {
            $parts[] = 'AI Orbit classifies the pricing model as ' . $this->pricing_type_label . (filled($this->pricing_basis) ? ' (' . $this->pricing_basis . ')' : '') . '.';
        }

        return implode("\n\n", array_values(array_unique(array_filter($parts))));
    }
    public function pricingSources()
    {
        return $this->hasMany(ModelPricingSource::class, 'ai_model_id');
    }

    public function evidenceSources()
    {
        return $this->hasMany(ModelEvidenceSource::class, 'ai_model_id');
    }

    public function getPricingTypeLabelAttribute(): string
    {
        return match ($this->pricing_type) {
            'token' => 'Token API',
            'open_weight' => 'Open-weight / hosted',
            'image' => 'Image pricing',
            'video' => 'Video pricing',
            'audio' => 'Audio pricing',
            'text_to_speech' => 'Text-to-speech pricing',
            'speech_to_text' => 'Speech-to-text pricing',
            'voice' => 'Voice / session pricing',
            'regional' => 'Regional pricing',
            'legacy' => 'Historical pricing',
            'scientific' => 'Research model',
            'research' => 'Research / deployment',
            'provider_dependent' => 'Provider-dependent',
            default => ($this->input_price_per_million !== null || $this->output_price_per_million !== null)
                ? 'Token API'
                : 'Pricing not classified',
        };
    }

    public function getPricingVerificationLabelAttribute(): string
    {
        return match ($this->pricing_verification_status) {
            'verified' => 'Verified token pricing',
            'verified_structure' => 'Pricing structure verified',
            'verified_specialized' => 'Verified specialized pricing',
            'verified_unit_only' => 'Pricing unit verified',
            'provider_dependent' => 'Provider-dependent pricing',
            'historical_unpriced' => 'Historical price not pinned',
            'regional' => 'Region-dependent pricing',
            'not_applicable' => 'Token pricing not applicable',
            'not_pinned' => 'Current price not pinned',
            default => 'Pricing verification pending',
        };
    }

    public function hasTokenPricing(): bool
    {
        return $this->pricing_type === 'token'
            || $this->input_price_per_million !== null
            || $this->output_price_per_million !== null;
    }

    public function pricingHistory()
    {
        return $this->hasMany(ModelPricingHistory::class, 'ai_model_id');
    }


}
