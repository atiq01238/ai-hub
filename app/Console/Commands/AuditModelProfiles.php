<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AuditModelProfiles extends Command
{
    protected $signature = 'models:profile-audit';

    protected $description = 'Report AI model identity and profile-data coverage after verified enrichment.';

    public function handle(): int
    {
        if (! Schema::hasColumn('ai_models', 'identity_status') ||
            ! Schema::hasColumn('ai_models', 'profile_verified_at')) {
            $this->error('Model identity/profile fields are missing. Run php artisan migrate first.');
            return self::FAILURE;
        }

        $total = AiModel::query()->count();
        $verifiedIdentity = AiModel::query()->where('identity_status', 'verified')->count();
        $needsMapping = AiModel::query()->where('identity_status', 'needs_mapping')->count();
        $profileVerified = AiModel::query()->whereNotNull('profile_verified_at')->count();
        $withSource = AiModel::query()->whereNotNull('official_source_url')->where('official_source_url', '!=', '')->count();
        $withNotes = AiModel::query()->whereNotNull('capability_notes')->where('capability_notes', '!=', '')->count();
        $withRelease = AiModel::query()->whereNotNull('release_date')->count();
        $withContext = AiModel::query()->whereNotNull('context_window')->where('context_window', '!=', '')->count();
        $withCapabilities = AiModel::query()->whereNotNull('capabilities')->where('capabilities', '!=', '[]')->count();
        $withFeatureTerms = AiModel::query()->whereHas('featureTerms')->count();
        $withUseCases = AiModel::query()->whereHas('useCaseTerms')->count();

        $this->table(['Metric', 'Count', 'Coverage'], [
            ['Total model rows', $total, '100%'],
            ['Identity verified', $verifiedIdentity, $this->pct($verifiedIdentity, $total)],
            ['Needs exact identity mapping', $needsMapping, $this->pct($needsMapping, $total)],
            ['Profile verified', $profileVerified, $this->pct($profileVerified, $total)],
            ['Official source URL', $withSource, $this->pct($withSource, $total)],
            ['Rich profile / capability notes', $withNotes, $this->pct($withNotes, $total)],
            ['Release date', $withRelease, $this->pct($withRelease, $total)],
            ['Context window', $withContext, $this->pct($withContext, $total)],
            ['Legacy capability array', $withCapabilities, $this->pct($withCapabilities, $total)],
            ['Taxonomy feature links', $withFeatureTerms, $this->pct($withFeatureTerms, $total)],
            ['Taxonomy use-case links', $withUseCases, $this->pct($withUseCases, $total)],
        ]);

        $unverified = AiModel::query()
            ->with('company:id,name')
            ->where(function ($query) {
                $query->whereNull('profile_verified_at')
                    ->orWhere('identity_status', '!=', 'verified');
            })
            ->orderBy('identity_status')
            ->orderBy('name')
            ->get(['id','company_id','name','identity_status','profile_verified_at']);

        if ($unverified->isNotEmpty()) {
            $this->newLine();
            $this->warn('Profiles still requiring identity resolution or profile verification:');
            $this->table(['Company', 'Model', 'Identity status', 'Profile verified'], $unverified->map(fn ($model) => [
                $model->company?->name ?: '—',
                $model->name,
                $model->identity_status ?: 'unreviewed',
                $model->profile_verified_at?->toDateString() ?: 'No',
            ])->all());
        }

        return self::SUCCESS;
    }

    private function pct(int $count, int $total): string
    {
        return $total > 0 ? number_format(($count / $total) * 100, 1).'%' : '0.0%';
    }
}
