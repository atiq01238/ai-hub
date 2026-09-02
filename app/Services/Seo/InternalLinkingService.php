<?php

namespace App\Services\Seo;

use App\Models\AiModel;
use App\Models\Company;
use App\Models\Comparison;
use App\Models\Tool;
use Illuminate\Support\Collection;

class InternalLinkingService
{
    public function comparisonsForModel(AiModel $model, int $limit = 4): Collection
    {
        $candidates = $this->publishedComparisons('model')
            ->filter(fn (Comparison $comparison) => $this->storedIds($comparison)->contains((int) $model->id));

        return $this->validateCandidates($candidates, $limit);
    }

    public function comparisonsForTool(Tool $tool, int $limit = 4): Collection
    {
        $candidates = $this->publishedComparisons('tool')
            ->filter(fn (Comparison $comparison) => $this->storedIds($comparison)->contains((int) $tool->id));

        return $this->validateCandidates($candidates, $limit);
    }

    public function comparisonsForCompany(Company $company, int $limit = 6): Collection
    {
        $toolIds = $company->tools()
            ->where('status', 'published')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $modelIds = $company->models()
            ->whereIn('status', ['active', 'preview'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $candidates = $this->publishedComparisons()
            ->filter(function (Comparison $comparison) use ($toolIds, $modelIds) {
                $ids = $this->storedIds($comparison);
                $companyIds = $comparison->comparable_type === 'tool' ? $toolIds : $modelIds;

                return $ids->intersect($companyIds)->isNotEmpty();
            });

        return $this->validateCandidates($candidates, $limit);
    }

    private function publishedComparisons(?string $type = null): Collection
    {
        return Comparison::query()
            ->where('status', 'published')
            ->when($type, fn ($query) => $query->where('comparable_type', $type))
            ->orderByDesc('last_verified_at')
            ->orderByDesc('views')
            ->latest('id')
            ->get();
    }

    private function validateCandidates(Collection $candidates, int $limit): Collection
    {
        return $candidates
            ->filter(function (Comparison $comparison) {
                try {
                    $comparison->setRelation('resolved_items', $comparison->publicItems());
                    return $comparison->getRelation('resolved_items')->count() >= 2;
                } catch (\Throwable $e) {
                    report($e);
                    return false;
                }
            })
            ->take($limit)
            ->values();
    }

    private function storedIds(Comparison $comparison): Collection
    {
        return collect($comparison->item_ids ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }
}
