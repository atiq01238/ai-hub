<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Company;
use App\Models\Tool;
use App\Services\ModelFamilyResolver;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LogoImportController extends Controller
{
    public function index(Request $request, ModelFamilyResolver $families)
    {
        $type = in_array($request->query('type'), ['company', 'tool', 'model'], true)
            ? $request->query('type')
            : 'company';

        $modelFamilyMeta = [];

        if ($type === 'company') {
            $items = Company::query()
                ->whereNull('logo_path')
                ->orderBy('name')
                ->paginate(40)
                ->withQueryString();
        } elseif ($type === 'tool') {
            $items = Tool::query()
                ->with('company')
                ->whereNull('logo_path')
                ->whereNotNull('website')
                ->orderBy('name')
                ->paginate(40)
                ->withQueryString();
        } else {
            $items = AiModel::query()
                ->with('company')
                ->orderBy('name')
                ->paginate(40)
                ->withQueryString();

            // Build family statistics once for the complete catalog. The actual
            // card list remains paginated, but every visible model can show the
            // correct family size and dedicated-logo coverage.
            $allModels = AiModel::query()->with('company')->orderBy('name')->get();
            $groups = $allModels->groupBy(fn (AiModel $model) => $families->key($model));

            foreach ($groups as $group) {
                /** @var \App\Models\AiModel $first */
                $first = $group->first();
                $familyName = $families->name($first);
                $dedicated = $group->whereNotNull('logo_path')->count();

                foreach ($group as $model) {
                    $modelFamilyMeta[$model->id] = [
                        'name' => $familyName,
                        'count' => $group->count(),
                        'dedicated' => $dedicated,
                    ];
                }
            }
        }

        $counts = [
            'company' => Company::whereNull('logo_path')->count(),
            'tool' => Tool::whereNull('logo_path')->count(),
            'model' => AiModel::count(),
            'model_dedicated' => AiModel::whereNotNull('logo_path')->count(),
            'model_fallback' => AiModel::whereNull('logo_path')->whereHas('company', fn ($q) => $q->whereNotNull('logo_path'))->count(),
            'model_unresolved' => AiModel::whereNull('logo_path')->where(function ($q) {
                $q->whereNull('company_id')->orWhereDoesntHave('company', fn ($company) => $company->whereNotNull('logo_path'));
            })->count(),
        ];

        return view('data-import.logos', compact('type', 'items', 'counts', 'modelFamilyMeta'));
    }

    public function saveCompany(int $id)
    {
        return $this->saveWebsiteCandidate('company', Company::findOrFail($id));
    }

    public function saveTool(int $id)
    {
        return $this->saveWebsiteCandidate('tool', Tool::findOrFail($id));
    }

    private function saveWebsiteCandidate(string $type, $item)
    {
        $website = (string) $item->website;
        if ($website === '') {
            return back()->with('error', 'This record has no website to derive a logo candidate from.');
        }

        $host = parse_url($website, PHP_URL_HOST) ?: parse_url('https://'.$website, PHP_URL_HOST);
        if (! $host) {
            return back()->with('error', 'Unable to determine the website domain.');
        }

        $candidate = 'https://www.google.com/s2/favicons?domain='.urlencode($host).'&sz=256';

        try {
            $response = Http::timeout(12)->retry(1, 250)->get($candidate);
            if (! $response->successful()) {
                throw new \RuntimeException('Logo source returned HTTP '.$response->status());
            }

            $body = $response->body();
            if (strlen($body) < 100 || strlen($body) > 2 * 1024 * 1024) {
                throw new \RuntimeException('Logo response size is invalid.');
            }

            $contentType = strtolower((string) $response->header('Content-Type'));
            $extension = str_contains($contentType, 'png') ? 'png'
                : ((str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg')) ? 'jpg'
                : (str_contains($contentType, 'webp') ? 'webp' : 'png'));

            $folder = $type === 'company' ? 'companies' : 'tools/logos';
            $path = $folder.'/'.Str::slug($item->name).'-'.substr(sha1($host), 0, 7).'.'.$extension;

            Storage::disk('public')->put($path, $body);
            $oldPath = $item->logo_path;
            $item->update(['logo_path' => $path]);
            $this->deleteLogoIfUnused($oldPath, $path);

            return back()->with('status', $item->name.' logo candidate saved locally. Review the public page for brand accuracy.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Logo fetch failed for '.$item->name.': '.$e->getMessage());
        }
    }

    public function uploadModelLogo(Request $request, int $id, ModelFamilyResolver $families)
    {
        $data = $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'scope' => ['required', 'in:model,family'],
        ]);

        $model = AiModel::with('company')->findOrFail($id);
        $familyName = $families->name($model);
        $extension = strtolower($request->file('logo')->extension() ?: 'png');
        $baseName = $data['scope'] === 'family'
            ? Str::slug(($model->company?->name ?: 'independent').'-'.$familyName)
            : Str::slug($model->name.'-'.($model->version ?: 'model'));
        $filename = ($baseName ?: 'model-logo').'-'.Str::lower(Str::random(8)).'.'.$extension;
        $path = $request->file('logo')->storeAs('models/logos', $filename, 'public');

        $targets = $data['scope'] === 'family'
            ? AiModel::query()->where('company_id', $model->company_id)->get()
                ->filter(fn (AiModel $candidate) => $families->name($candidate) === $familyName)
            : collect([$model]);

        if ($targets->isEmpty()) {
            Storage::disk('public')->delete($path);
            return back()->with('error', 'No matching model records were found for this logo.');
        }

        $oldPaths = $targets->pluck('logo_path')->filter()->unique()->values()->all();

        try {
            DB::transaction(function () use ($targets, $path) {
                AiModel::query()->whereIn('id', $targets->pluck('id'))->update(['logo_path' => $path]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            report($e);
            return back()->with('error', 'Model logo could not be saved. No catalog records were changed.');
        }

        foreach ($oldPaths as $oldPath) {
            $this->deleteLogoIfUnused($oldPath, $path);
        }

        $message = $data['scope'] === 'family'
            ? $familyName.' family logo saved and applied to '.$targets->count().' model'.($targets->count() === 1 ? '' : 's').'.'
            : $model->name.' dedicated model logo saved.';

        return back()->with('status', $message);
    }

    public function removeModelLogo(Request $request, int $id, ModelFamilyResolver $families)
    {
        $data = $request->validate([
            'scope' => ['required', 'in:model,family'],
        ]);

        $model = AiModel::with('company')->findOrFail($id);
        $familyName = $families->name($model);
        $targets = $data['scope'] === 'family'
            ? AiModel::query()->where('company_id', $model->company_id)->get()
                ->filter(fn (AiModel $candidate) => $families->name($candidate) === $familyName)
            : collect([$model]);

        $oldPaths = $targets->pluck('logo_path')->filter()->unique()->values()->all();

        DB::transaction(function () use ($targets) {
            AiModel::query()->whereIn('id', $targets->pluck('id'))->update(['logo_path' => null]);
        });

        foreach ($oldPaths as $oldPath) {
            $this->deleteLogoIfUnused($oldPath);
        }

        $message = $data['scope'] === 'family'
            ? 'Dedicated logo removed from '.$familyName.' family. Company fallback is active where available.'
            : $model->name.' dedicated logo removed. Company fallback is active where available.';

        return back()->with('status', $message);
    }

    public function useCompanyFallback(Request $request, int $id)
    {
        $model = AiModel::with('company')->findOrFail($id);
        if (! $model->company?->logo_path) {
            return back()->with('error', 'The model company does not have a logo yet.');
        }

        return back()->with('status', $model->name.' already uses '.$model->company->name.' as its automatic logo fallback.');
    }

    public static function candidateUrl($item): ?string
    {
        $website = (string) ($item->website ?? $item->company?->website ?? '');
        $host = $website ? (parse_url($website, PHP_URL_HOST) ?: parse_url('https://'.$website, PHP_URL_HOST)) : null;

        return $host ? 'https://www.google.com/s2/favicons?domain='.urlencode($host).'&sz=256' : null;
    }

    private function deleteLogoIfUnused(?string $oldPath, ?string $replacementPath = null): void
    {
        if (! $oldPath || $oldPath === $replacementPath) {
            return;
        }

        $stillUsed = Company::where('logo_path', $oldPath)->exists()
            || Tool::where('logo_path', $oldPath)->exists()
            || AiModel::where('logo_path', $oldPath)->exists();

        if (! $stillUsed) {
            $diskPath = MediaUrl::diskPath($oldPath);
            if ($diskPath) Storage::disk('public')->delete($diskPath);
        }
    }
}
