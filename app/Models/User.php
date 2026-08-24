<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use App\Notifications\VerifyEmailNotification;

class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function communityComments()
    {
        return $this->hasMany(CommunityComment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function submissions()
    {
        return $this->hasMany(Submission::class);
    }

    public function savedItems()
    {
        return $this->hasMany(SavedItem::class);
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function emailPreference()
    {
        return $this->hasOne(EmailPreference::class);
    }

    public function emailDeliveryLogs()
    {
        return $this->hasMany(EmailDeliveryLog::class);
    }

    public function interactions()
    {
        return $this->hasMany(UserInteraction::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    public function reportsFiled()
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function reportsReceived()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function suspendedBy()
    {
        return $this->belongsTo(self::class, 'suspended_by')->withTrashed();
    }

    public function deletedBy()
    {
        return $this->belongsTo(self::class, 'deleted_by')->withTrashed();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_enabled' => 'boolean',
            'suspended_at' => 'datetime',
            'suspended_until' => 'datetime',
            'last_login_at' => 'datetime',
            'community_trusted_at' => 'datetime',
            'community_restricted_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }


    public function isCommunityTrusted(): bool
    {
        return $this->community_trust_level === 'trusted';
    }

    public function isCommunityRestricted(): bool
    {
        return $this->community_trust_level === 'restricted';
    }

    public function communityTrustLabel(): string
    {
        return match ($this->community_trust_level) {
            'trusted' => 'Trusted',
            'restricted' => 'Restricted',
            default => 'Normal',
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin'
            && $this->status === 'active'
            && $this->roleModel?->isSystemRole();
    }

    public function hasAdminPanelAccess(): bool
    {
        return $this->role === 'admin' && $this->status === 'active';
    }

    public function canAccessModule(string $module, string $action): bool
    {
        if ($this->role !== 'admin' || $this->status !== 'active') {
            return false;
        }

        $role = $this->roleModel;

        // Super Admin is a protected system role and always has full access.
        if ($role?->isSystemRole()) {
            return true;
        }

        // Backward compatibility only: the normalization migration assigns
        // legacy role-less administrators to Super Admin. Until that migration
        // is run, an old administrator without role_id must not be locked out.
        if (! $this->role_id) {
            return true;
        }

        if (! $role) {
            return false;
        }

        // A direct permission row is authoritative, including an explicit OFF.
        $direct = $role->permissions
            ->where('module', $module)
            ->where('action', $action)
            ->first();

        if ($direct) {
            return (bool) $direct->allowed;
        }

        // Backward-compatible bridge for roles created before the professional
        // permission catalogue existed. Once the new matrix is saved, direct
        // rows are created and these fallbacks stop applying automatically.
        $legacyFallbacks = [
            'System Health' => 'Security',
            'Error Monitoring' => 'Security',
            'API Monitoring' => 'Security',
            'Automation' => 'Security',
            'Roles & Permissions' => 'Security',
            'Backups' => 'Security',
            'Data Verification' => 'AI News',
            'Source Reliability' => 'AI News',
            'News Sources' => 'AI News',
            'Notifications' => 'Settings',
            'Feature Flags' => 'Settings',
            'Integrations' => 'Settings',
            'SEO' => 'Settings',
            'AI Companies' => 'AI Tools',
            'Taxonomy' => 'AI Tools',
            'Content' => 'AI News',
        ];

        $legacyModule = $legacyFallbacks[$module] ?? null;
        if ($legacyModule) {
            return $role->permissions
                ->where('module', $legacyModule)
                ->where('action', $action)
                ->first()?->allowed ?? false;
        }

        return false;
    }

    public function restoreIfSuspensionExpired(): bool
    {
        if ($this->status !== 'suspended' || ! $this->suspended_until?->isPast()) {
            return false;
        }

        $this->forceFill([
            'status' => 'active',
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_until' => null,
            'suspended_by' => null,
        ])->save();

        return true;
    }
    public function preference()
    {
        return $this->hasOne(UserPreference::class);
    }

}
