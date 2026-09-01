<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolTechnicalProfile extends Model
{
    public const API_STATUSES = [
        'unknown' => 'Not yet verified',
        'available' => 'API available',
        'limited' => 'Limited / selected API access',
        'unavailable' => 'No public API',
        'not_applicable' => 'Not applicable',
    ];

    public const OPEN_SOURCE_STATUSES = [
        'unknown' => 'Not yet verified',
        'open_source' => 'Open source',
        'source_available' => 'Source available',
        'mixed' => 'Mixed open + proprietary',
        'proprietary' => 'Proprietary',
        'not_applicable' => 'Not applicable',
    ];

    public const SELF_HOSTING_STATUSES = [
        'unknown' => 'Not yet verified',
        'supported' => 'Self-hosting supported',
        'enterprise_only' => 'Enterprise / private deployment only',
        'unsupported' => 'Self-hosting not supported',
        'not_applicable' => 'Not applicable',
    ];

    public const COMMERCIAL_USE_STATUSES = [
        'unknown' => 'Not yet verified',
        'allowed' => 'Commercial use allowed',
        'restricted' => 'Commercial use restricted',
        'plan_dependent' => 'Depends on plan / license',
        'not_allowed' => 'Commercial use not allowed',
        'not_applicable' => 'Not applicable',
    ];

    public const TRAINING_POLICIES = [
        'unknown' => 'Not yet verified',
        'not_used' => 'Customer data not used for training',
        'opt_out' => 'Training use with opt-out / controls',
        'used' => 'Customer data may be used for training',
        'plan_dependent' => 'Depends on plan / workspace settings',
        'not_applicable' => 'Not applicable',
    ];

    public const SSO_STATUSES = [
        'unknown' => 'Not yet verified',
        'available' => 'SSO / SAML available',
        'enterprise_only' => 'Enterprise-only SSO / SAML',
        'unavailable' => 'SSO / SAML unavailable',
        'not_applicable' => 'Not applicable',
    ];

    public const DEPLOYMENT_MODES = [
        'SaaS', 'Cloud', 'API', 'Desktop', 'Mobile', 'Local', 'Self-hosted',
        'On-Premises', 'Embedded', 'Private Cloud', 'Hybrid',
    ];

    protected $fillable = [
        'tool_id',
        'api_status', 'api_docs_url', 'api_source_id',
        'open_source_status', 'license_name', 'repository_url', 'repository_source_id',
        'self_hosting_status', 'deployment_modes', 'deployment_source_id', 'commercial_use_status', 'terms_source_id',
        'supported_languages', 'region_availability', 'availability_source_id',
        'data_training_policy', 'data_retention_note', 'privacy_summary', 'privacy_source_id',
        'security_summary', 'security_certifications', 'compliance_certifications', 'data_residency',
        'sso_status', 'security_source_id', 'last_reviewed_at',
    ];

    protected $casts = [
        'deployment_modes' => 'array',
        'supported_languages' => 'array',
        'region_availability' => 'array',
        'security_certifications' => 'array',
        'compliance_certifications' => 'array',
        'data_residency' => 'array',
        'last_reviewed_at' => 'datetime',
    ];

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function apiSource() { return $this->belongsTo(ToolSource::class, 'api_source_id'); }
    public function repositorySource() { return $this->belongsTo(ToolSource::class, 'repository_source_id'); }
    public function deploymentSource() { return $this->belongsTo(ToolSource::class, 'deployment_source_id'); }
    public function termsSource() { return $this->belongsTo(ToolSource::class, 'terms_source_id'); }
    public function availabilitySource() { return $this->belongsTo(ToolSource::class, 'availability_source_id'); }
    public function privacySource() { return $this->belongsTo(ToolSource::class, 'privacy_source_id'); }
    public function securitySource() { return $this->belongsTo(ToolSource::class, 'security_source_id'); }
}
