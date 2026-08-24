<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Company;
use App\Models\Feature;
use App\Models\NewsItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Services\Seo\EntitySeoService;
use App\Services\Taxonomy\TaxonomyNormalizer;

class ModelController extends Controller
{
    public function index(Request $request, TaxonomyNormalizer $taxonomy)
    {
        $filters = $request->validate([
            'q' => ['nullable','string','max:100'], 'company' => ['nullable','string','max:100'],
            'status' => ['nullable','in:active,preview'], 'capability' => ['nullable','string','max:80'],
            'context' => ['nullable','in:128k,200k,256k,1m'], 'price' => ['nullable','in:free,under1,under5'],
            'sort' => ['nullable','in:benchmark,newest,price_low,name'], 'view' => ['nullable','in:grid,list'],
        ]);

        $query = AiModel::query()->with(['company','tool','featureTerms'])->whereIn('status',['active','preview']);

        if ($q = trim($filters['q'] ?? '')) {
            $query->where(function (Builder $builder) use ($q) {
                $builder->where('name','like',"%{$q}%")->orWhere('version','like',"%{$q}%")
                    ->orWhere('capability_notes','like',"%{$q}%")
                    ->orWhereHas('company', fn (Builder $company) => $company->where('name','like',"%{$q}%"));
            });
        }
        if (!empty($filters['company'])) $query->whereHas('company', fn (Builder $q) => $q->where('slug',$filters['company']));
        if (!empty($filters['status'])) $query->where('status',$filters['status']);
        if (!empty($filters['capability'])) {
            $capability = $filters['capability'];
            $canonical = $taxonomy->canonicalFeatureNames([$capability])[0] ?? $capability;
            $feature = Feature::active()->where(fn (Builder $q) => $q->where('slug', $capability)->orWhere('name', $canonical))->first();
            $query->where(function (Builder $builder) use ($feature, $capability) {
                if ($feature) {
                    $builder->whereHas('featureTerms', fn (Builder $q) => $q->whereKey($feature->id))
                        ->orWhereJsonContains('capabilities', $feature->name);
                } else {
                    $builder->whereJsonContains('capabilities', $capability);
                }
            });
        }
        if (!empty($filters['context'])) $query->where('context_window', strtoupper($filters['context']));
        if (($filters['price'] ?? null) === 'free') $query->where('input_price_per_million', 0);
        if (($filters['price'] ?? null) === 'under1') $query->where('input_price_per_million','<',1);
        if (($filters['price'] ?? null) === 'under5') $query->where('input_price_per_million','<',5);

        match ($filters['sort'] ?? 'benchmark') {
            'newest' => $query->orderByDesc('release_date'),
            'price_low' => $query->orderByRaw('input_price_per_million IS NULL, input_price_per_million ASC'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('benchmark_score')->orderByDesc('release_date'),
        };

        $models = $query->paginate(12)->withQueryString();
        $companies = Company::query()->withCount(['models' => fn ($q) => $q->whereIn('status',['active','preview'])])
            ->whereHas('models', fn ($q) => $q->whereIn('status',['active','preview']))->orderByDesc('models_count')->get();
        $capabilities = Feature::active()
            ->whereHas('models', fn (Builder $q) => $q->whereIn('status', ['active','preview']))
            ->withCount(['models' => fn (Builder $q) => $q->whereIn('status', ['active','preview'])])
            ->orderByDesc('models_count')->orderBy('name')->get();
        $stats = [
            'models' => AiModel::whereIn('status',['active','preview'])->count(),
            'providers' => $companies->count(),
            'topScore' => AiModel::whereIn('status',['active','preview'])->max('benchmark_score'),
            'latest' => AiModel::whereIn('status',['active','preview'])->whereNotNull('release_date')->orderByDesc('release_date')->value('release_date'),
        ];
        $leaders = AiModel::with('company')->whereIn('status',['active','preview'])->whereNotNull('benchmark_score')->orderByDesc('benchmark_score')->take(5)->get();

        return view('frontend.models.index', compact('models','companies','capabilities','stats','leaders'));
    }

    public function show(AiModel $model, EntitySeoService $seoService)
    {
        abort_unless(in_array($model->status, ['active','preview'], true), 404);
        $model->load(['company','tool','featureTerms','useCaseTerms','tagTerms','pricingSources','benchmarkResults' => fn ($q) => $q->with('benchmark')->where('verified',true)->latest('tested_at')]);

        $relatedModels = AiModel::with(['company','tool'])->whereIn('status',['active','preview'])->whereKeyNot($model->id)
            ->when($model->company_id, fn (Builder $q) => $q->where('company_id',$model->company_id))
            ->orderByDesc('benchmark_score')->take(4)->get();
        if ($relatedModels->count() < 4) {
            $relatedModels = $relatedModels->concat(AiModel::with('company')->whereIn('status',['active','preview'])->whereKeyNot($model->id)
                ->whereNotIn('id',$relatedModels->pluck('id'))->orderByDesc('benchmark_score')->take(4-$relatedModels->count())->get());
        }

        $latestNews = NewsItem::with('company')->where('status','published')->whereNull('duplicate_of_id')
            ->where(function (Builder $q) use ($model) {
                if ($model->company_id) $q->where('company_id',$model->company_id); else $q->whereRaw('1=0');
                $q->orWhere('headline','like','%'.$model->name.'%')->orWhere('summary','like','%'.$model->name.'%');
            })->latest('published_at')->take(4)->get();

        $benchmarks = collect($model->benchmarks ?? [])->map(fn ($score,$name) => ['name'=>$name,'score'=>(float)$score]);
        $capabilities = collect($model->capabilities ?? [])->filter()->values();
        $labResults = collect();
        $labStats = ['average' => null, 'tests' => 0, 'runs' => 0, 'verified' => 0, 'types' => collect()];

        $seo = $seoService->model($model);
        $seoSchemas = $seoService->schemas('model', $model, $seo);

        return view('frontend.models.show', compact('model','relatedModels','latestNews','benchmarks','capabilities','labResults','labStats','seo','seoSchemas'));
    }
}
