<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Feature;
use App\Models\Platform;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\ToolSource;
use App\Models\ToolTechnicalProfile;
use App\Models\UseCase;
use App\Services\Tools\PlatformNormalizer;
use App\Services\Tools\ToolCommercialProfileService;
use App\Services\Tools\ToolSourceService;
use App\Services\Tools\ToolProfileIntelligenceService;
use App\Services\Tools\ToolAdvancedIntelligenceService;
use App\Services\Tools\ToolDataConfidenceService;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ToolController extends Controller
{
    public function __construct(
        private readonly ToolCommercialProfileService $commercialProfile,
        private readonly ToolSourceService $sourceService,
        private readonly PlatformNormalizer $platformNormalizer,
        private readonly ToolProfileIntelligenceService $profileIntelligence,
        private readonly ToolAdvancedIntelligenceService $advancedIntelligence,
    ) {}

    public function index(Request $request)
    {
        $query = Tool::query()->with(['company', 'category', 'subcategoryTerm'])->withCount('models');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn ($q) => $q->where('name','like',"%{$search}%")
                ->orWhere('short_description','like',"%{$search}%")
                ->orWhere('description','like',"%{$search}%"));
        }
        if ($request->filled('category_id')) $query->where('category_id', $request->integer('category_id'));
        if ($request->filled('company_id')) $query->where('company_id', $request->integer('company_id'));
        if ($request->filled('status') && in_array($request->status, ['draft','published','archived'], true)) $query->where('status', $request->status);
        if ($request->filled('pricing')) {
            $pricing = mb_strtolower((string) $request->pricing);
            $this->commercialProfile->applyFilter($query, $pricing);
        }
        if ($request->filled('rating')) $query->where('rating', '>=', (float) $request->rating);

        $tools = $query->latest()->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get(['id','name']);
        $categories = Category::product()->active()->orderBy('sort_order')->orderBy('name')->get(['id','name']);
        return view('tools.index', compact('tools','companies','categories'));
    }

    public function create()
    {
        return view('tools.form', $this->formOptions());
    }

    public function store(Request $request)
    {
        [$data,$featureIds,$tagIds,$useCaseIds,$platformIds,$sourceData,$featureProfiles,$useCaseProfiles,$lifecycleData,$advancedData,$advancedSources,$integrationData] = $this->fromRequest($request);
        $tool = DB::transaction(function () use ($data,$featureIds,$tagIds,$useCaseIds,$platformIds,$sourceData,$featureProfiles,$useCaseProfiles,$lifecycleData,$advancedData,$advancedSources,$integrationData) {
            $tool = Tool::create($data);
            $tool->featureTerms()->sync($featureIds);
            $tool->tagTerms()->sync($tagIds);
            $tool->useCaseTerms()->sync($useCaseIds);
            $tool->platformTerms()->sync($platformIds);
            $primarySource = $this->persistAdminSource($tool, $sourceData);
            $this->profileIntelligence->syncFeatureProfiles($tool, $featureProfiles, $primarySource);
            $this->profileIntelligence->syncUseCaseProfiles($tool, $useCaseProfiles, $primarySource);
            $this->persistLifecycleEvidence($tool, $lifecycleData, $primarySource);
            $this->advancedIntelligence->syncTechnicalProfile($tool, $advancedData, $advancedSources, auth()->id());
            $this->advancedIntelligence->syncIntegrations($tool, $integrationData['names'], $integrationData['source_url'], $integrationData['status'], auth()->id(), true);
            return $tool;
        });
        return redirect()->route('admin.tools.show',$tool->id)->with('status','Tool created. Source and platform data were saved using the V3 evidence foundation.');
    }

    public function show(int $id, ToolDataConfidenceService $confidence)
    {
        $tool = Tool::with([
                'company','category','subcategoryTerm','featureTerms','tagTerms','useCaseTerms','models',
                'platformTerms' => fn ($q) => $q->orderBy('sort_order'),
                'integrationTerms' => fn ($q) => $q->orderBy('name'),
                'technicalProfile', 'factEvidence', 'pricingPlans.sources',
                'sources' => fn ($q) => $q->where('enabled', true)->latest('verified_at')->latest('id'),
            ])
            ->withCount(['models','reviews'])->findOrFail($id);
        $dataConfidence = $confidence->score($tool);
        return view('tools.show', compact('tool','dataConfidence'));
    }

    public function edit(int $id)
    {
        $tool = Tool::with([
            'featureTerms:id','tagTerms:id','useCaseTerms:id','platformTerms:id','integrationTerms:id,name','technicalProfile',
            'sources' => fn ($q) => $q->where('enabled', true)->orderByDesc('is_primary')->latest('id'),
        ])->findOrFail($id);

        // Safe pre-backfill fallback: editing an older tool must not silently clear its legacy platforms.
        $legacyPlatformIds = [];
        if ($tool->platformTerms->isEmpty() && ! empty($tool->platforms)) {
            $normalized = $this->platformNormalizer->normalize($tool->platforms);
            if ($normalized['unknown'] === []) {
                $legacyPlatformIds = $this->platformNormalizer->idsForNames($normalized['canonical']);
            }
        }

        return view('tools.form', ['tool'=>$tool, 'legacyPlatformIds'=>$legacyPlatformIds] + $this->formOptions());
    }

    public function update(Request $request, int $id)
    {
        $tool = Tool::findOrFail($id);
        [$data,$featureIds,$tagIds,$useCaseIds,$platformIds,$sourceData,$featureProfiles,$useCaseProfiles,$lifecycleData,$advancedData,$advancedSources,$integrationData] = $this->fromRequest($request,$tool);
        DB::transaction(function () use ($tool,$data,$featureIds,$tagIds,$useCaseIds,$platformIds,$sourceData,$featureProfiles,$useCaseProfiles,$lifecycleData,$advancedData,$advancedSources,$integrationData) {
            $tool->update($data);
            $tool->featureTerms()->sync($featureIds);
            $tool->tagTerms()->sync($tagIds);
            $tool->useCaseTerms()->sync($useCaseIds);
            $tool->platformTerms()->sync($platformIds);
            $primarySource = $this->persistAdminSource($tool, $sourceData);
            $this->profileIntelligence->syncFeatureProfiles($tool, $featureProfiles, $primarySource);
            $this->profileIntelligence->syncUseCaseProfiles($tool, $useCaseProfiles, $primarySource);
            $this->persistLifecycleEvidence($tool, $lifecycleData, $primarySource);
            $this->advancedIntelligence->syncTechnicalProfile($tool, $advancedData, $advancedSources, auth()->id());
            $this->advancedIntelligence->syncIntegrations($tool, $integrationData['names'], $integrationData['source_url'], $integrationData['status'], auth()->id(), true);
        });
        return redirect()->route('admin.tools.show',$tool->id)->with('status','Tool updated. Canonical platforms and source evidence were synchronized.');
    }

    public function destroy(int $id)
    {
        $tool = Tool::withCount('models')->findOrFail($id);
        foreach (['logo_path','cover_image_path','og_image_path'] as $column) {
            if ($tool->{$column}) {
                $path = MediaUrl::diskPath($tool->{$column});
                if ($path) Storage::disk('public')->delete($path);
            }
        }
        $tool->delete();
        return redirect()->route('admin.tools.index')->with('status','Tool deleted. Linked models were kept and detached safely.');
    }

    private function formOptions(): array
    {
        return [
            'companies' => Company::orderBy('name')->get(),
            'categories' => Category::product()->active()->orderBy('sort_order')->orderBy('name')->get(),
            'subcategories' => Subcategory::active()->with('category')->orderBy('sort_order')->orderBy('name')->get(),
            'features' => Feature::active()->orderBy('group')->orderBy('sort_order')->orderBy('name')->get(),
            'tags' => Tag::active()->orderBy('sort_order')->orderBy('name')->get(),
            'useCases' => UseCase::active()->orderBy('sort_order')->orderBy('name')->get(),
            'platformTerms' => Platform::active()->orderBy('sort_order')->orderBy('name')->get(),
            'sourceStatuses' => ['pending' => 'Pending verification', 'verified' => 'Verified'],
            'productStatuses' => Tool::PRODUCT_STATUSES,
            'apiStatuses' => ToolTechnicalProfile::API_STATUSES,
            'openSourceStatuses' => ToolTechnicalProfile::OPEN_SOURCE_STATUSES,
            'selfHostingStatuses' => ToolTechnicalProfile::SELF_HOSTING_STATUSES,
            'commercialUseStatuses' => ToolTechnicalProfile::COMMERCIAL_USE_STATUSES,
            'trainingPolicies' => ToolTechnicalProfile::TRAINING_POLICIES,
            'ssoStatuses' => ToolTechnicalProfile::SSO_STATUSES,
            'deploymentModes' => ToolTechnicalProfile::DEPLOYMENT_MODES,
        ];
    }

    private function fromRequest(Request $request, ?Tool $tool = null): array
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'company_id' => ['nullable','exists:companies,id'],
            'category_id' => ['nullable', Rule::exists('categories','id')->where(fn($q)=>$q->where('type','product')->where('is_active',true))],
            'subcategory_id' => ['nullable', Rule::exists('subcategories','id')->where(fn($q)=>$q->where('is_active',true))],
            'website' => ['nullable','url','max:255'],
            'launch_date' => ['nullable','date'],
            'short_description' => ['nullable','string','max:255'],
            'description' => ['nullable','string'],
            'status' => ['required', Rule::in(['draft','published','archived'])],
            'product_status' => ['nullable', Rule::in(array_keys(Tool::PRODUCT_STATUSES))],
            'product_status_note' => ['nullable','string','max:2000'],
            'lifecycle_source_url' => ['nullable','url','max:2000'],
            'lifecycle_verification_status' => ['nullable', Rule::in(['pending','verified'])],
            'api_status' => ['nullable', Rule::in(array_keys(ToolTechnicalProfile::API_STATUSES))],
            'api_docs_url' => ['nullable','url','max:2000'],
            'api_source_url' => ['nullable','url','max:2000'],
            'api_source_verification_status' => ['nullable', Rule::in(['pending','verified'])],
            'open_source_status' => ['nullable', Rule::in(array_keys(ToolTechnicalProfile::OPEN_SOURCE_STATUSES))],
            'license_name' => ['nullable','string','max:255'],
            'repository_url' => ['nullable','url','max:2000'],
            'repository_source_url' => ['nullable','url','max:2000'],
            'repository_source_verification_status' => ['nullable', Rule::in(['pending','verified'])],
            'self_hosting_status' => ['nullable', Rule::in(array_keys(ToolTechnicalProfile::SELF_HOSTING_STATUSES))],
            'deployment_modes' => ['nullable','array'],
            'deployment_modes.*' => ['string', Rule::in(ToolTechnicalProfile::DEPLOYMENT_MODES)],
            'deployment_source_url' => ['nullable','url','max:2000'],
            'deployment_source_verification_status' => ['nullable', Rule::in(['pending','verified'])],
            'commercial_use_status' => ['nullable', Rule::in(array_keys(ToolTechnicalProfile::COMMERCIAL_USE_STATUSES))],
            'terms_source_url' => ['nullable','url','max:2000'],
            'terms_source_verification_status' => ['nullable', Rule::in(['pending','verified'])],
            'supported_languages_text' => ['nullable','string','max:4000'],
            'region_availability_text' => ['nullable','string','max:4000'],
            'availability_source_url' => ['nullable','url','max:2000'],
            'availability_source_verification_status' => ['nullable', Rule::in(['pending','verified'])],
            'data_training_policy' => ['nullable', Rule::in(array_keys(ToolTechnicalProfile::TRAINING_POLICIES))],
            'data_retention_note' => ['nullable','string','max:4000'],
            'privacy_summary' => ['nullable','string','max:8000'],
            'privacy_source_url' => ['nullable','url','max:2000'],
            'privacy_source_verification_status' => ['nullable', Rule::in(['pending','verified'])],
            'security_summary' => ['nullable','string','max:8000'],
            'security_certifications_text' => ['nullable','string','max:4000'],
            'compliance_certifications_text' => ['nullable','string','max:4000'],
            'data_residency_text' => ['nullable','string','max:4000'],
            'sso_status' => ['nullable', Rule::in(array_keys(ToolTechnicalProfile::SSO_STATUSES))],
            'security_source_url' => ['nullable','url','max:2000'],
            'security_source_verification_status' => ['nullable', Rule::in(['pending','verified'])],
            'integrations_text' => ['nullable','string','max:12000'],
            'integration_source_url' => ['nullable','url','max:2000'],
            'integration_source_verification_status' => ['nullable', Rule::in(['pending','verified'])],
            'feature_profiles' => ['nullable','array'],
            'feature_profiles.*.description' => ['nullable','string','max:3000'],
            'feature_profiles.*.verification_status' => ['nullable', Rule::in(['pending','verified'])],
            'feature_profiles.*.notes' => ['nullable','string','max:2000'],
            'use_case_profiles' => ['nullable','array'],
            'use_case_profiles.*.fit_note' => ['nullable','string','max:3000'],
            'use_case_profiles.*.verification_status' => ['nullable', Rule::in(['pending','verified'])],
            'use_case_profiles.*.notes' => ['nullable','string','max:2000'],
            'feature_ids' => ['nullable','array'],
            'feature_ids.*' => ['integer', Rule::exists('features','id')->where(fn($q)=>$q->where('is_active',true))],
            'tag_ids' => ['nullable','array'],
            'tag_ids.*' => ['integer', Rule::exists('tags','id')->where(fn($q)=>$q->where('is_active',true))],
            'use_case_ids' => ['nullable','array'],
            'use_case_ids.*' => ['integer', Rule::exists('use_cases','id')->where(fn($q)=>$q->where('is_active',true))],
            'platform_ids' => ['nullable','array'],
            'platform_ids.*' => ['integer', Rule::exists('platforms','id')->where(fn($q)=>$q->where('is_active',true))],
            'source_url' => ['nullable','url','max:2000'],
            'source_verification_status' => ['nullable', Rule::in(['pending','verified'])],
            'seo_title' => ['nullable','string','max:255'],
            'meta_description' => ['nullable','string','max:255'],
            'slug' => ['nullable','string','max:255'],
            'logo' => ['nullable','image','max:2048'],
            'cover_image' => ['nullable','image','max:4096'],
            'og_image' => ['nullable','image','max:2048'],
        ]);

        if (!empty($data['subcategory_id'])) {
            $subcategory = Subcategory::find($data['subcategory_id']);
            if (!$subcategory || empty($data['category_id']) || (int)$subcategory->category_id !== (int)$data['category_id']) {
                throw ValidationException::withMessages(['subcategory_id'=>'Selected subcategory does not belong to the selected product category.']);
            }
        }

        $featureIds = collect($data['feature_ids'] ?? [])->map(fn($id)=>(int)$id)->unique()->values()->all();
        $tagIds = collect($data['tag_ids'] ?? [])->map(fn($id)=>(int)$id)->unique()->values()->all();
        $useCaseIds = collect($data['use_case_ids'] ?? [])->map(fn($id)=>(int)$id)->unique()->values()->all();
        $platformIds = collect($data['platform_ids'] ?? [])->map(fn($id)=>(int)$id)->unique()->values()->all();
        $sourceData = [
            'url' => trim((string) ($data['source_url'] ?? '')),
            'status' => (string) ($data['source_verification_status'] ?? 'pending'),
        ];
        $featureProfiles = (array) ($data['feature_profiles'] ?? []);
        $useCaseProfiles = (array) ($data['use_case_profiles'] ?? []);
        $lifecycleData = [
            'url' => trim((string) ($data['lifecycle_source_url'] ?? '')),
            'status' => (string) ($data['lifecycle_verification_status'] ?? 'pending'),
        ];
        $advancedData = [
            'api_status' => $data['api_status'] ?? $tool?->technicalProfile?->api_status ?? 'unknown',
            'api_docs_url' => $data['api_docs_url'] ?? null,
            'open_source_status' => $data['open_source_status'] ?? $tool?->technicalProfile?->open_source_status ?? 'unknown',
            'license_name' => $data['license_name'] ?? null,
            'repository_url' => $data['repository_url'] ?? null,
            'self_hosting_status' => $data['self_hosting_status'] ?? $tool?->technicalProfile?->self_hosting_status ?? 'unknown',
            'deployment_modes' => $data['deployment_modes'] ?? [],
            'commercial_use_status' => $data['commercial_use_status'] ?? $tool?->technicalProfile?->commercial_use_status ?? 'unknown',
            'supported_languages' => $this->advancedIntelligence->splitList($data['supported_languages_text'] ?? ''),
            'region_availability' => $this->advancedIntelligence->splitList($data['region_availability_text'] ?? ''),
            'data_training_policy' => $data['data_training_policy'] ?? $tool?->technicalProfile?->data_training_policy ?? 'unknown',
            'data_retention_note' => $data['data_retention_note'] ?? null,
            'privacy_summary' => $data['privacy_summary'] ?? null,
            'security_summary' => $data['security_summary'] ?? null,
            'security_certifications' => $this->advancedIntelligence->splitList($data['security_certifications_text'] ?? ''),
            'compliance_certifications' => $this->advancedIntelligence->splitList($data['compliance_certifications_text'] ?? ''),
            'data_residency' => $this->advancedIntelligence->splitList($data['data_residency_text'] ?? ''),
            'sso_status' => $data['sso_status'] ?? $tool?->technicalProfile?->sso_status ?? 'unknown',
        ];
        $advancedSources = [];
        foreach (['api','repository','deployment','terms','availability','privacy','security'] as $group) {
            $advancedSources[$group] = [
                'url' => trim((string) ($data[$group.'_source_url'] ?? '')),
                'status' => (string) ($data[$group.'_source_verification_status'] ?? 'pending'),
            ];
        }
        $integrationData = [
            'names' => $this->advancedIntelligence->splitList($data['integrations_text'] ?? ''),
            'source_url' => trim((string) ($data['integration_source_url'] ?? '')),
            'status' => (string) ($data['integration_source_verification_status'] ?? 'pending'),
        ];
        $data['product_status'] = (string) ($data['product_status'] ?? $tool?->product_status ?? 'unknown');
        unset($data['feature_ids'],$data['tag_ids'],$data['use_case_ids'],$data['platform_ids'],$data['source_url'],$data['source_verification_status'],$data['feature_profiles'],$data['use_case_profiles'],$data['lifecycle_source_url'],$data['lifecycle_verification_status'],
            $data['api_status'],$data['api_docs_url'],$data['api_source_url'],$data['api_source_verification_status'],$data['open_source_status'],$data['license_name'],$data['repository_url'],$data['repository_source_url'],$data['repository_source_verification_status'],
            $data['self_hosting_status'],$data['deployment_modes'],$data['deployment_source_url'],$data['deployment_source_verification_status'],$data['commercial_use_status'],$data['terms_source_url'],$data['terms_source_verification_status'],$data['supported_languages_text'],$data['region_availability_text'],$data['availability_source_url'],$data['availability_source_verification_status'],
            $data['data_training_policy'],$data['data_retention_note'],$data['privacy_summary'],$data['privacy_source_url'],$data['privacy_source_verification_status'],$data['security_summary'],$data['security_certifications_text'],$data['compliance_certifications_text'],$data['data_residency_text'],$data['sso_status'],$data['security_source_url'],$data['security_source_verification_status'],
            $data['integrations_text'],$data['integration_source_url'],$data['integration_source_verification_status']);

        $data['capabilities'] = Feature::whereIn('id',$featureIds)->orderBy('name')->pluck('name')->all();
        $data['tags'] = Tag::whereIn('id',$tagIds)->orderBy('name')->pluck('name')->all();
        $data['platforms'] = Platform::whereIn('id',$platformIds)->orderBy('sort_order')->pluck('name')->all();
        $data['subcategory'] = !empty($data['subcategory_id']) ? Subcategory::whereKey($data['subcategory_id'])->value('name') : null;

        // pricing_models is a compatibility cache derived from pricing_plans. The Tool editor no longer owns it.
        if ($tool) {
            $data['pricing_models'] = $this->commercialProfile->expectedLabels($tool);
        }

        $baseSlug = Str::slug($data['slug'] ?: $data['name']) ?: 'tool';
        $slug = $baseSlug; $counter = 2;
        while (Tool::where('slug',$slug)->when($tool,fn($q)=>$q->where('id','!=',$tool->id))->exists()) $slug = $baseSlug.'-'.$counter++;
        $data['slug'] = $slug;
        $data['published_at'] = $data['status']==='published' ? ($tool?->published_at ?: now()) : null;

        foreach (['logo'=>'logo_path','cover_image'=>'cover_image_path','og_image'=>'og_image_path'] as $input=>$column) {
            if ($request->hasFile($input)) {
                if ($tool?->{$column}) {
                    $path = MediaUrl::diskPath($tool->{$column});
                    if ($path) Storage::disk('public')->delete($path);
                }
                $data[$column] = $request->file($input)->store('tools','public');
            }
            unset($data[$input]);
        }

        return [$data,$featureIds,$tagIds,$useCaseIds,$platformIds,$sourceData,$featureProfiles,$useCaseProfiles,$lifecycleData,$advancedData,$advancedSources,$integrationData];
    }

    private function persistAdminSource(Tool $tool, array $sourceData): ?ToolSource
    {
        if (($sourceData['url'] ?? '') === '') return $this->profileIntelligence->primarySource($tool);

        $status = ($sourceData['status'] ?? 'pending') === 'verified' ? 'verified' : 'pending';
        return $this->sourceService->upsert(
            tool: $tool,
            url: $sourceData['url'],
            sourceType: 'official_product',
            verificationStatus: $status,
            sourceName: $tool->name.' official source',
            verifiedAt: $status === 'verified' ? now() : null,
            verifiedBy: $status === 'verified' ? auth()->id() : null,
            factType: 'identity',
            factKey: 'official_source',
            primary: true,
        );
    }

    private function persistLifecycleEvidence(Tool $tool, array $lifecycleData, ?ToolSource $fallbackSource = null): void
    {
        $productStatus = $tool->product_status ?: 'unknown';
        if ($productStatus === 'unknown') {
            $tool->updateQuietly(['product_status_source_id' => null, 'product_status_verified_at' => null]);
            return;
        }

        $url = trim((string) ($lifecycleData['url'] ?? ''));
        $status = ($lifecycleData['status'] ?? 'pending') === 'verified' ? 'verified' : 'pending';
        $source = null;

        if ($url !== '') {
            $source = $this->sourceService->upsert(
                tool: $tool,
                url: $url,
                sourceType: $this->sourceService->inferSourceType($url),
                verificationStatus: $status,
                sourceName: $tool->name.' lifecycle source',
                verifiedAt: $status === 'verified' ? now() : null,
                verifiedBy: $status === 'verified' ? auth()->id() : null,
                factType: 'lifecycle',
                factKey: 'product_status',
                primary: false,
            );
        }

        $lifecycleVerified = $status === 'verified' && $source?->verification_status === 'verified';
        $tool->updateQuietly([
            'product_status_source_id' => $source?->id,
            'product_status_verified_at' => $lifecycleVerified ? ($source->verified_at ?: now()) : null,
        ]);
    }

}
