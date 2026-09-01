@extends('layouts.admin')
@section('title', isset($tool) ? 'Edit AI Tool' : 'Add AI Tool')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/tools.css') }}">
@endpush

@section('content')
@php
    $tool ??= null;
    $old = fn($key, $default = null) => old($key, $tool->{$key} ?? $default);
    $selectedFeatures = collect(old('feature_ids', $tool?->featureTerms?->pluck('id')->all() ?? []))->map(fn($id)=>(int)$id)->all();
    $selectedTags = collect(old('tag_ids', $tool?->tagTerms?->pluck('id')->all() ?? []))->map(fn($id)=>(int)$id)->all();
    $selectedUseCases = collect(old('use_case_ids', $tool?->useCaseTerms?->pluck('id')->all() ?? []))->map(fn($id)=>(int)$id)->all();
    $pricingModels = (array) ($tool?->pricing_models ?? []);
    $storedPlatformIds = $tool?->platformTerms?->pluck('id')->all() ?? [];
    if (!$storedPlatformIds && !empty($legacyPlatformIds ?? [])) $storedPlatformIds = $legacyPlatformIds;
    $selectedPlatformIds = collect(old('platform_ids', $storedPlatformIds))->map(fn($id)=>(int)$id)->all();
    $primarySource = $tool?->sources?->firstWhere('is_primary', true) ?: $tool?->sources?->first();
    $sourceUrl = old('source_url', $primarySource?->source_url ?? '');
    $sourceVerificationStatus = old('source_verification_status', $primarySource?->verification_status ?? 'pending');
    $featureProfileTerms = $tool?->featureTerms?->keyBy('id') ?? collect();
    $useCaseProfileTerms = $tool?->useCaseTerms?->keyBy('id') ?? collect();
    $lifecycleSource = $tool?->product_status_source_id ? $tool?->sources?->firstWhere('id', $tool->product_status_source_id) : null;
    $lifecycleSourceUrl = old('lifecycle_source_url', $lifecycleSource?->source_url ?? '');
    $lifecycleVerificationStatus = old('lifecycle_verification_status', $tool?->product_status_verified_at ? 'verified' : 'pending');
    $technical = $tool?->technicalProfile;
    $sourceById = fn($id) => $id ? $tool?->sources?->firstWhere('id', (int)$id) : null;
    $apiSource = $sourceById($technical?->api_source_id);
    $repositorySource = $sourceById($technical?->repository_source_id);
    $deploymentSource = $sourceById($technical?->deployment_source_id);
    $termsSource = $sourceById($technical?->terms_source_id);
    $availabilitySource = $sourceById($technical?->availability_source_id);
    $privacySource = $sourceById($technical?->privacy_source_id);
    $securitySource = $sourceById($technical?->security_source_id);
    $integrationSource = $sourceById($tool?->integrationTerms?->first()?->pivot?->tool_source_id);
    $integrationNames = old('integrations_text', $tool?->integrationTerms?->pluck('name')->implode(' | ') ?? '');
    $listText = fn($value) => is_array($value) ? implode(' | ', $value) : (string)($value ?? '');
@endphp

<form action="{{ $tool ? route('admin.tools.update', $tool->id) : route('admin.tools.store') }}" method="POST" enctype="multipart/form-data" class="tool-editor">
    @csrf
    @if($tool) @method('PUT') @endif

    <x-page-header title="{{ $tool ? 'Edit AI Tool' : 'Add AI Tool' }}" subtitle="Maintain product identity, taxonomy, capabilities, media and publishing metadata." :breadcrumb="['AI Management','AI Tools',$tool ? 'Edit' : 'Add']">
        <x-slot:actions>
            <a href="{{ $tool ? route('admin.tools.show', $tool->id) : route('admin.tools.index') }}" class="btn btn-ghost btn-sm"><i data-lucide="x"></i> Cancel</a>
            <button class="btn btn-secondary btn-sm" type="submit" name="status" value="draft"><i data-lucide="file-text"></i> Save Draft</button>
            <button class="btn btn-primary btn-sm" type="submit" name="status" value="published"><i data-lucide="send"></i> Publish</button>
        </x-slot:actions>
    </x-page-header>

    @if($errors->any())
        <div class="alert alert-danger tools-alert tools-errors">
            <i data-lucide="circle-alert"></i>
            <div><strong>Please fix the following fields:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        </div>
    @endif

    <div class="tool-editor-grid">
        <main class="tool-editor-main">
            <section class="card tool-form-card">
                <div class="tool-form-heading"><span class="tool-form-icon"><i data-lucide="badge-info"></i></span><div><h3>Product Identity</h3><p>Core directory information shown across the admin and public product experience.</p></div></div>
                <div class="tool-form-grid tool-form-grid--2">
                    <div class="form-field"><label for="tool-name">Tool Name <span>*</span></label><input id="tool-name" class="input" name="name" required value="{{ $old('name') }}" placeholder="e.g. ChatGPT"></div>
                    <div class="form-field"><label for="tool-slug">Slug</label><input id="tool-slug" class="input" name="slug" value="{{ $old('slug') }}" placeholder="Auto-generated when empty"></div>
                    <div class="form-field"><label for="tool-company">Company</label><select id="tool-company" class="select" name="company_id"><option value="">Independent / No company</option>@foreach($companies as $company)<option value="{{ $company->id }}" @selected((string)$old('company_id') === (string)$company->id)>{{ $company->name }}</option>@endforeach</select></div>
                    <div class="form-field"><label for="tool-website">Website</label><input id="tool-website" class="input" type="url" name="website" value="{{ $old('website') }}" placeholder="https://example.com"></div>
                    <div class="form-field"><label for="tool-category">Category</label><select id="tool-category" class="select" name="category_id"><option value="">No category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)$old('category_id') === (string)$category->id)>{{ $category->name }}</option>@endforeach</select></div>
                    <div class="form-field"><label for="tool-subcategory">Subcategory</label><select id="tool-subcategory" class="select" name="subcategory_id"><option value="">No subcategory</option>@foreach($subcategories as $subcategory)<option value="{{ $subcategory->id }}" data-category="{{ $subcategory->category_id }}" @selected((string)$old('subcategory_id') === (string)$subcategory->id)>{{ $subcategory->name }}</option>@endforeach</select></div>
                    <div class="form-field"><label for="tool-launch">Launch Date</label><input id="tool-launch" class="input" type="date" name="launch_date" value="{{ $old('launch_date') ? \Illuminate\Support\Carbon::parse($old('launch_date'))->format('Y-m-d') : '' }}"></div>
                    <div class="form-field tool-form-grid__wide"><label for="tool-short-description">Short Description</label><input id="tool-short-description" class="input" name="short_description" maxlength="255" value="{{ $old('short_description') }}" placeholder="One concise sentence describing the product"></div>
                </div>
                <div class="form-field tool-description-field"><label for="tool-description">Full Description</label><textarea id="tool-description" class="input" rows="8" name="description" placeholder="Explain the product, core use cases and audience...">{{ $old('description') }}</textarea></div>
            </section>

            <section class="card tool-form-card">
                <div class="tool-form-heading"><span class="tool-form-icon"><i data-lucide="sparkles"></i></span><div><h3>Features & Capabilities</h3><p>Select normalized capabilities used for discovery and comparisons.</p></div></div>
                <div class="tool-check-grid">
                    @forelse($features as $feature)
                        <label class="tool-check-card {{ in_array((int)$feature->id, $selectedFeatures, true) ? 'is-selected' : '' }}">
                            <input type="checkbox" name="feature_ids[]" value="{{ $feature->id }}" data-profile-toggle="feature-{{ $feature->id }}" @checked(in_array((int)$feature->id, $selectedFeatures, true))>
                            <span><i data-lucide="check"></i></span><strong>{{ $feature->name }}</strong>
                        </label>
                    @empty
                        <div class="tool-inline-empty"><i data-lucide="info"></i> No features yet. Add them from AI Management → Features.</div>
                    @endforelse
                </div>
                @if($features->isNotEmpty())
                    <div class="tool-profile-evidence-list">
                        @foreach($features as $feature)
                            @php $profileTerm = $featureProfileTerms->get($feature->id); $pivot = $profileTerm?->pivot; @endphp
                            <div class="tool-profile-evidence" data-profile-editor="feature-{{ $feature->id }}" @if(!in_array((int)$feature->id, $selectedFeatures, true)) hidden @endif>
                                <div class="tool-profile-evidence__head"><strong>{{ $feature->name }}</strong><span>Tool-specific capability evidence</span></div>
                                <div class="tool-form-grid tool-form-grid--2">
                                    <div class="form-field tool-form-grid__wide"><label>Description</label><textarea class="input" rows="2" name="feature_profiles[{{ $feature->id }}][description]" placeholder="Describe how this capability works in this tool. Leave blank to use the canonical taxonomy description.">{{ old("feature_profiles.{$feature->id}.description", $pivot?->description) }}</textarea></div>
                                    <div class="form-field"><label>Evidence status</label><select class="select" name="feature_profiles[{{ $feature->id }}][verification_status]">@foreach($sourceStatuses as $value => $label)<option value="{{ $value }}" @selected(old("feature_profiles.{$feature->id}.verification_status", $pivot?->verification_status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="form-field"><label>Evidence note</label><input class="input" name="feature_profiles[{{ $feature->id }}][notes]" value="{{ old("feature_profiles.{$feature->id}.notes", $pivot?->notes) }}" placeholder="Optional evidence/context note"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="tool-inline-empty"><i data-lucide="shield-check"></i> Existing mappings stay Pending until explicitly verified. Blank descriptions fall back to the canonical Feature description instead of generated filler text.</div>
                @endif
            </section>

            <section class="card tool-form-card">
                <div class="tool-form-heading"><span class="tool-form-icon"><i data-lucide="target"></i></span><div><h3>Use Cases</h3><p>Describe what people use this product to accomplish. These power discovery and SEO landing pages.</p></div></div>
                <div class="tool-check-grid tool-check-grid--tags">
                    @forelse($useCases as $useCase)
                        <label class="tool-check-card {{ in_array((int)$useCase->id, $selectedUseCases, true) ? 'is-selected' : '' }}">
                            <input type="checkbox" name="use_case_ids[]" value="{{ $useCase->id }}" data-profile-toggle="usecase-{{ $useCase->id }}" @checked(in_array((int)$useCase->id, $selectedUseCases, true))>
                            <span><i data-lucide="check"></i></span><strong>{{ $useCase->name }}</strong>
                        </label>
                    @empty
                        <div class="tool-inline-empty"><i data-lucide="info"></i> No use cases yet. Run Taxonomy v2 sync or add them from AI Management → Use Cases.</div>
                    @endforelse
                </div>
                @if($useCases->isNotEmpty())
                    <div class="tool-profile-evidence-list">
                        @foreach($useCases as $useCase)
                            @php $profileTerm = $useCaseProfileTerms->get($useCase->id); $pivot = $profileTerm?->pivot; @endphp
                            <div class="tool-profile-evidence" data-profile-editor="usecase-{{ $useCase->id }}" @if(!in_array((int)$useCase->id, $selectedUseCases, true)) hidden @endif>
                                <div class="tool-profile-evidence__head"><strong>{{ $useCase->name }}</strong><span>Best-for evidence</span></div>
                                <div class="tool-form-grid tool-form-grid--2">
                                    <div class="form-field tool-form-grid__wide"><label>Fit note</label><textarea class="input" rows="2" name="use_case_profiles[{{ $useCase->id }}][fit_note]" placeholder="Why is this tool a good fit for this use case?">{{ old("use_case_profiles.{$useCase->id}.fit_note", $pivot?->fit_note) }}</textarea></div>
                                    <div class="form-field"><label>Evidence status</label><select class="select" name="use_case_profiles[{{ $useCase->id }}][verification_status]">@foreach($sourceStatuses as $value => $label)<option value="{{ $value }}" @selected(old("use_case_profiles.{$useCase->id}.verification_status", $pivot?->verification_status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="form-field"><label>Evidence note</label><input class="input" name="use_case_profiles[{{ $useCase->id }}][notes]" value="{{ old("use_case_profiles.{$useCase->id}.notes", $pivot?->notes) }}" placeholder="Optional evidence/context note"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="card tool-form-card">
                <div class="tool-form-heading"><span class="tool-form-icon"><i data-lucide="tags"></i></span><div><h3>Tags</h3><p>Use controlled tags to improve search, curation and related-content matching.</p></div></div>
                <div class="tool-check-grid tool-check-grid--tags">
                    @forelse($tags as $tag)
                        <label class="tool-check-card {{ in_array((int)$tag->id, $selectedTags, true) ? 'is-selected' : '' }}">
                            <input type="checkbox" name="tag_ids[]" value="{{ $tag->id }}" @checked(in_array((int)$tag->id, $selectedTags, true))>
                            <span><i data-lucide="check"></i></span><strong>{{ $tag->name }}</strong>
                        </label>
                    @empty
                        <div class="tool-inline-empty"><i data-lucide="info"></i> No tags yet. Add them from AI Management → Tags.</div>
                    @endforelse
                </div>
            </section>
            <section class="card tool-form-card">
                <div class="tool-form-heading"><span class="tool-form-icon"><i data-lucide="terminal-square"></i></span><div><h3>Technical Access & Licensing</h3><p>Structured API, deployment, open-source, licensing and commercial-use facts. Unknown is preferred over an unsupported claim.</p></div></div>
                <div class="tool-form-grid tool-form-grid--2">
                    <div class="form-field"><label>API status</label><select class="select" name="api_status">@foreach($apiStatuses as $value => $label)<option value="{{ $value }}" @selected(old('api_status', $technical?->api_status ?? 'unknown') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-field"><label>API docs URL</label><input class="input" type="url" name="api_docs_url" value="{{ old('api_docs_url', $technical?->api_docs_url) }}" placeholder="https://.../api/docs"></div>
                    <div class="form-field"><label>API evidence URL</label><input class="input" type="url" name="api_source_url" value="{{ old('api_source_url', $apiSource?->source_url) }}"></div>
                    <div class="form-field"><label>API evidence status</label><select class="select" name="api_source_verification_status">@foreach($sourceStatuses as $value => $label)<option value="{{ $value }}" @selected(old('api_source_verification_status', $apiSource?->verification_status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</select></div>

                    <div class="form-field"><label>Open-source status</label><select class="select" name="open_source_status">@foreach($openSourceStatuses as $value => $label)<option value="{{ $value }}" @selected(old('open_source_status', $technical?->open_source_status ?? 'unknown') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-field"><label>License</label><input class="input" name="license_name" value="{{ old('license_name', $technical?->license_name) }}" placeholder="MIT, Apache-2.0, Proprietary..."></div>
                    <div class="form-field"><label>Repository URL</label><input class="input" type="url" name="repository_url" value="{{ old('repository_url', $technical?->repository_url) }}"></div>
                    <div class="form-field"><label>Repository / license evidence</label><input class="input" type="url" name="repository_source_url" value="{{ old('repository_source_url', $repositorySource?->source_url) }}"></div>
                    <div class="form-field"><label>Repository evidence status</label><select class="select" name="repository_source_verification_status">@foreach($sourceStatuses as $value => $label)<option value="{{ $value }}" @selected(old('repository_source_verification_status', $repositorySource?->verification_status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-field"><label>Self-hosting</label><select class="select" name="self_hosting_status">@foreach($selfHostingStatuses as $value => $label)<option value="{{ $value }}" @selected(old('self_hosting_status', $technical?->self_hosting_status ?? 'unknown') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-field"><label>Deployment / self-hosting evidence</label><input class="input" type="url" name="deployment_source_url" value="{{ old('deployment_source_url', $deploymentSource?->source_url) }}"></div>
                    <div class="form-field"><label>Deployment evidence status</label><select class="select" name="deployment_source_verification_status">@foreach($sourceStatuses as $value => $label)<option value="{{ $value }}" @selected(old('deployment_source_verification_status', $deploymentSource?->verification_status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-field"><label>Commercial use</label><select class="select" name="commercial_use_status">@foreach($commercialUseStatuses as $value => $label)<option value="{{ $value }}" @selected(old('commercial_use_status', $technical?->commercial_use_status ?? 'unknown') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-field"><label>Terms / commercial-use source</label><input class="input" type="url" name="terms_source_url" value="{{ old('terms_source_url', $termsSource?->source_url) }}"></div>
                    <div class="form-field"><label>Terms evidence status</label><select class="select" name="terms_source_verification_status">@foreach($sourceStatuses as $value => $label)<option value="{{ $value }}" @selected(old('terms_source_verification_status', $termsSource?->verification_status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</select></div>
                </div>
                <div class="tool-side-group"><label>Deployment modes</label><div class="tool-option-stack">@foreach($deploymentModes as $mode)<label class="tool-option"><input type="checkbox" name="deployment_modes[]" value="{{ $mode }}" @checked(in_array($mode, old('deployment_modes', $technical?->deployment_modes ?? []), true))><span></span>{{ $mode }}</label>@endforeach</div></div>
            </section>

            <section class="card tool-form-card">
                <div class="tool-form-heading"><span class="tool-form-icon"><i data-lucide="shield-check"></i></span><div><h3>Privacy, Security & Availability</h3><p>Source-backed trust facts for enterprise and privacy-sensitive buyers. Do not infer certifications or training policy.</p></div></div>
                <div class="tool-form-grid tool-form-grid--2">
                    <div class="form-field"><label>Customer-data training policy</label><select class="select" name="data_training_policy">@foreach($trainingPolicies as $value => $label)<option value="{{ $value }}" @selected(old('data_training_policy', $technical?->data_training_policy ?? 'unknown') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-field"><label>Data retention note</label><input class="input" name="data_retention_note" value="{{ old('data_retention_note', $technical?->data_retention_note) }}" placeholder="e.g. provider-stated retention policy"></div>
                    <div class="form-field tool-form-grid__wide"><label>Privacy summary</label><textarea class="input" rows="3" name="privacy_summary">{{ old('privacy_summary', $technical?->privacy_summary) }}</textarea></div>
                    <div class="form-field"><label>Privacy source</label><input class="input" type="url" name="privacy_source_url" value="{{ old('privacy_source_url', $privacySource?->source_url) }}"></div>
                    <div class="form-field"><label>Privacy evidence status</label><select class="select" name="privacy_source_verification_status">@foreach($sourceStatuses as $value => $label)<option value="{{ $value }}" @selected(old('privacy_source_verification_status', $privacySource?->verification_status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</select></div>

                    <div class="form-field tool-form-grid__wide"><label>Security summary</label><textarea class="input" rows="3" name="security_summary">{{ old('security_summary', $technical?->security_summary) }}</textarea></div>
                    <div class="form-field"><label>Security certifications</label><input class="input" name="security_certifications_text" value="{{ old('security_certifications_text', $listText($technical?->security_certifications)) }}" placeholder="SOC 2 | ISO 27001"></div>
                    <div class="form-field"><label>Compliance</label><input class="input" name="compliance_certifications_text" value="{{ old('compliance_certifications_text', $listText($technical?->compliance_certifications)) }}" placeholder="GDPR | HIPAA"></div>
                    <div class="form-field"><label>Data residency</label><input class="input" name="data_residency_text" value="{{ old('data_residency_text', $listText($technical?->data_residency)) }}" placeholder="US | EU"></div>
                    <div class="form-field"><label>SSO / SAML</label><select class="select" name="sso_status">@foreach($ssoStatuses as $value => $label)<option value="{{ $value }}" @selected(old('sso_status', $technical?->sso_status ?? 'unknown') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="form-field"><label>Security source</label><input class="input" type="url" name="security_source_url" value="{{ old('security_source_url', $securitySource?->source_url) }}"></div>
                    <div class="form-field"><label>Security evidence status</label><select class="select" name="security_source_verification_status">@foreach($sourceStatuses as $value => $label)<option value="{{ $value }}" @selected(old('security_source_verification_status', $securitySource?->verification_status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</select></div>

                    <div class="form-field"><label>Supported languages</label><input class="input" name="supported_languages_text" value="{{ old('supported_languages_text', $listText($technical?->supported_languages)) }}" placeholder="English | Spanish | ..."></div>
                    <div class="form-field"><label>Region availability</label><input class="input" name="region_availability_text" value="{{ old('region_availability_text', $listText($technical?->region_availability)) }}" placeholder="Global | US | EU | ..."></div>
                    <div class="form-field"><label>Availability source</label><input class="input" type="url" name="availability_source_url" value="{{ old('availability_source_url', $availabilitySource?->source_url) }}"></div>
                    <div class="form-field"><label>Availability evidence status</label><select class="select" name="availability_source_verification_status">@foreach($sourceStatuses as $value => $label)<option value="{{ $value }}" @selected(old('availability_source_verification_status', $availabilitySource?->verification_status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</select></div>
                </div>
            </section>

            <section class="card tool-form-card">
                <div class="tool-form-heading"><span class="tool-form-icon"><i data-lucide="plug-zap"></i></span><div><h3>Structured Integrations</h3><p>One canonical integration name per item. This powers alternatives and buyer-facing compatibility data.</p></div></div>
                <div class="tool-form-grid tool-form-grid--2">
                    <div class="form-field tool-form-grid__wide"><label>Integrations</label><textarea class="input" rows="4" name="integrations_text" placeholder="Slack | Google Drive | Salesforce | GitHub">{{ $integrationNames }}</textarea></div>
                    <div class="form-field"><label>Integration documentation</label><input class="input" type="url" name="integration_source_url" value="{{ old('integration_source_url', $integrationSource?->source_url) }}"></div>
                    <div class="form-field"><label>Integration evidence status</label><select class="select" name="integration_source_verification_status">@foreach($sourceStatuses as $value => $label)<option value="{{ $value }}" @selected(old('integration_source_verification_status', $integrationSource?->verification_status ?? 'pending') === $value)>{{ $label }}</option>@endforeach</select></div>
                </div>
                <div class="tool-inline-empty"><i data-lucide="shield-alert"></i> Integrations are never inferred from a generic “Integrations” tag. They must be explicitly supplied from product/docs evidence.</div>
            </section>

        </main>

        <aside class="tool-editor-side">
            <section class="card tool-form-card tool-side-card">
                <div class="tool-form-heading tool-form-heading--compact"><span class="tool-form-icon"><i data-lucide="wallet-cards"></i></span><div><h3>Commercial Profile</h3><p>Pricing and supported platforms.</p></div></div>
                <div class="tool-side-group">
                    <label>Pricing Classification</label>
                    <div class="tool-inline-empty"><i data-lucide="database-zap"></i> Derived automatically from detailed Pricing Plans. Current cache: <strong>{{ $pricingModels ? implode(' + ', $pricingModels) : 'No plan data yet' }}</strong>.</div>
                </div>
                <div class="tool-side-group"><label>Platforms</label><div class="tool-option-stack">@foreach($platformTerms as $platform)<label class="tool-option"><input type="checkbox" name="platform_ids[]" value="{{ $platform->id }}" @checked(in_array((int)$platform->id, $selectedPlatformIds, true))><span></span>{{ $platform->name }}</label>@endforeach</div></div>
            </section>

            <section class="card tool-form-card tool-side-card">
                <div class="tool-form-heading tool-form-heading--compact"><span class="tool-form-icon"><i data-lucide="badge-check"></i></span><div><h3>Official Source</h3><p>Preserve evidence separately from the public website field. Published does not mean verified.</p></div></div>
                <div class="form-field"><label for="tool-source-url">Official Source URL</label><input id="tool-source-url" class="input" type="url" name="source_url" value="{{ $sourceUrl }}" placeholder="https://provider.com/product-or-docs"></div>
                <div class="form-field"><label for="tool-source-status">Evidence Status</label><select id="tool-source-status" class="select" name="source_verification_status">@foreach($sourceStatuses as $value => $label)<option value="{{ $value }}" @selected($sourceVerificationStatus === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="tool-inline-empty"><i data-lucide="shield-check"></i> Mark Verified only after checking the official source. Routine imports default to Pending unless verification is explicitly supplied.</div>
            </section>

            <section class="card tool-form-card tool-side-card">
                <div class="tool-form-heading tool-form-heading--compact"><span class="tool-form-icon"><i data-lucide="activity"></i></span><div><h3>Product Lifecycle</h3><p>Real-world product state, separate from Draft/Published CMS status.</p></div></div>
                <div class="form-field"><label for="product-status">Product Status</label><select id="product-status" class="select" name="product_status">@foreach($productStatuses as $value => $label)<option value="{{ $value }}" @selected($old('product_status','unknown') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="form-field"><label for="product-status-note">Lifecycle Note</label><textarea id="product-status-note" class="input" rows="3" name="product_status_note" placeholder="Optional: shutdown date, rebrand details, region limitation...">{{ $old('product_status_note') }}</textarea></div>
                <div class="form-field"><label for="lifecycle-source-url">Lifecycle Evidence URL</label><input id="lifecycle-source-url" class="input" type="url" name="lifecycle_source_url" value="{{ $lifecycleSourceUrl }}" placeholder="https://official-source.example/status-or-announcement"></div>
                <div class="form-field"><label for="lifecycle-status">Lifecycle Evidence Status</label><select id="lifecycle-status" class="select" name="lifecycle_verification_status">@foreach($sourceStatuses as $value => $label)<option value="{{ $value }}" @selected($lifecycleVerificationStatus === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="tool-inline-empty"><i data-lucide="shield-alert"></i> Existing tools default to Unknown. Do not mark Active/Discontinued/etc. as Verified without product/provider evidence.</div>
            </section>

            <section class="card tool-form-card tool-side-card">
                <div class="tool-form-heading tool-form-heading--compact"><span class="tool-form-icon"><i data-lucide="image"></i></span><div><h3>Media Assets</h3><p>Upload product artwork for directory presentation and sharing.</p></div></div>
                @foreach(['logo'=>'Logo','cover_image'=>'Cover Image','og_image'=>'Open Graph Image'] as $field => $label)
                    <div class="form-field tool-file-field"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" class="input" type="file" name="{{ $field }}" accept="image/*"></div>
                @endforeach
                @if($tool && ($tool->logo_path || $tool->cover_image_path || $tool->og_image_path))
                    <div class="tool-media-existing"><span>Existing assets</span><div>@if($tool->logo_path)<img src="{{ $tool->logo_url }}" alt="Current logo">@endif @if($tool->cover_image_path)<img src="{{ $tool->cover_image_url }}" alt="Current cover">@endif @if($tool->og_image_path)<img src="{{ $tool->og_image_url }}" alt="Current Open Graph image">@endif</div></div>
                @endif
            </section>

            <section class="card tool-form-card tool-side-card">
                <div class="tool-form-heading tool-form-heading--compact"><span class="tool-form-icon"><i data-lucide="search-check"></i></span><div><h3>SEO Metadata</h3><p>Search and social metadata for the product profile.</p></div></div>
                <div class="form-field"><label for="seo-title">SEO Title</label><input id="seo-title" class="input" name="seo_title" value="{{ $old('seo_title') }}" maxlength="255"></div>
                <div class="form-field"><label for="meta-description">Meta Description</label><textarea id="meta-description" class="input" name="meta_description" rows="4" maxlength="255">{{ $old('meta_description') }}</textarea></div>
            </section>
        </aside>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('change', function (event) {
    const input = event.target.closest('.tool-check-card input[type="checkbox"]');
    if (!input) return;
    input.closest('.tool-check-card').classList.toggle('is-selected', input.checked);
    const key = input.dataset.profileToggle;
    if (key) {
        const editor = document.querySelector('[data-profile-editor="' + key + '"]');
        if (editor) editor.hidden = !input.checked;
    }
});

(function () {
    const category = document.getElementById('tool-category');
    const subcategory = document.getElementById('tool-subcategory');
    if (!category || !subcategory) return;

    function filterSubcategories() {
        const categoryId = category.value;
        [...subcategory.options].forEach((option, index) => {
            if (index === 0) return option.hidden = false;
            option.hidden = !categoryId || option.dataset.category !== categoryId;
        });
        if (subcategory.selectedOptions[0]?.hidden) subcategory.value = '';
    }

    category.addEventListener('change', filterSubcategories);
    filterSubcategories();
})();
</script>
@endpush