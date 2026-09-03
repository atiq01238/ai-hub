<?php

namespace App\Services\Seo;

use App\Models\AiModel;
use App\Models\Tool;
use App\Models\ToolTechnicalProfile;
use Illuminate\Support\Str;

class EntitySeoService
{
    public function tool(Tool $tool): array
    {
        $tool->loadMissing([
            'company',
            'category',
            'featureTerms',
            'useCaseTerms',
            'sources',
            'technicalProfile',
            'integrationTerms',
        ]);

        if (! $tool->relationLoaded('pricingPlans')) {
            $tool->load('pricingPlans.sources');
        }

        $company = $tool->company?->name;
        $category = $tool->category?->name;
        $platforms = collect($tool->platforms ?? [])->filter()->take(3)->values();

        $title = $this->normalizeText(
            $tool->seo_title ?: $tool->name.' Review: Features, Pricing, Alternatives & AI Models'
        );
        $description = $this->normalizeText($tool->meta_description ?: Str::limit(trim(
            'Explore '.$tool->name.($company ? ' by '.$company : '').
            ($category ? ', a '.$category.' AI tool' : ' AI tool').
            '. See features, pricing, supported platforms, linked AI models, benchmarks, reviews and alternatives.'
        ), 158, ''));

        $faq = collect();
        $verifiedSources = $tool->sources
            ->filter(fn ($source) => (bool) $source->enabled && $source->verification_status === 'verified')
            ->keyBy('id');

        $verifiedFeatures = $tool->featureTerms
            ->filter(function ($feature) use ($verifiedSources) {
                $pivot = $feature->pivot;
                return $pivot
                    && $pivot->verification_status === 'verified'
                    && $pivot->tool_source_id
                    && $verifiedSources->has((int) $pivot->tool_source_id);
            })
            ->values();

        $verifiedUseCases = $tool->useCaseTerms
            ->filter(function ($useCase) use ($verifiedSources) {
                $pivot = $useCase->pivot;
                return $pivot
                    && $pivot->verification_status === 'verified'
                    && $pivot->tool_source_id
                    && $verifiedSources->has((int) $pivot->tool_source_id);
            })
            ->values();

        // Canonical taxonomy is still useful classification metadata even while field-level evidence is pending.
        $displayFeatures = $verifiedFeatures->isNotEmpty() ? $verifiedFeatures : $tool->featureTerms->values();
        $displayUseCases = $verifiedUseCases->isNotEmpty() ? $verifiedUseCases : $tool->useCaseTerms->values();

        // 1) Product overview — always useful, but expanded beyond a one-line fallback.
        $overviewLead = $this->sentenceExcerpt(
            $tool->description ?: $tool->short_description ?: $tool->name.' is an AI product listed in the AI Orbit directory.',
            2,
            330
        );
        $overviewContext = $this->joinSentenceParts([
            $company ? $tool->name.' is provided by '.$company : null,
            $category ? 'AI Orbit categorizes it under '.$category : null,
        ]);
        $overviewCapabilities = $displayFeatures->isNotEmpty()
            ? ($verifiedFeatures->isNotEmpty()
                ? 'Verified capability evidence on this profile includes '.$displayFeatures->pluck('name')->take(4)->join(', ', ' and ').'.'
                : 'Its structured capability profile includes '.$displayFeatures->pluck('name')->take(4)->join(', ', ' and ').'; field-level verification may still be pending for some capability mappings.')
            : null;

        $faq->push([
            'q' => 'What is '.$tool->name.'?',
            'a' => $this->composeAnswer([$overviewLead, $overviewContext, $overviewCapabilities]),
        ]);

        // 2) Provider — use the real company relationship rather than the old "associated with" template.
        if ($tool->company) {
            $companyDescription = $this->sentenceExcerpt($tool->company->description, 1, 240);
            $providerContext = $category
                ? 'Within AI Orbit, the product is cataloged in '.$category.', which describes its primary product category rather than the provider itself.'
                : 'AI Orbit keeps the provider relationship separate from the product profile so company and product information can be reviewed independently.';

            $faq->push([
                'q' => 'Who makes '.$tool->name.'?',
                'a' => $this->composeAnswer([
                    $tool->name.' is provided by '.$company.'.',
                    $companyDescription,
                    $providerContext,
                ]),
            ]);
        }

        // 3) Best-for — use actual Use Cases, never capabilities as a substitute.
        if ($displayUseCases->isNotEmpty()) {
            $useCaseNames = $displayUseCases->pluck('name')->filter()->take(4)->values();
            $fitNotes = $verifiedUseCases
                ->pluck('pivot.fit_note')
                ->filter()
                ->map(fn ($note) => $this->sentenceExcerpt($note, 1, 180))
                ->filter()
                ->take(2)
                ->values();

            $faq->push([
                'q' => 'What is '.$tool->name.' best for?',
                'a' => $this->composeAnswer([
                    $verifiedUseCases->isNotEmpty()
                        ? $tool->name.' is best suited to verified use cases including '.$useCaseNames->join(', ', ' and ').'.'
                        : 'AI Orbit currently maps '.$tool->name.' to structured use cases including '.$useCaseNames->join(', ', ' and ').'.',
                    $fitNotes->isNotEmpty()
                        ? $fitNotes->join(' ')
                        : ($verifiedUseCases->isNotEmpty()
                            ? 'These use cases are tied to verified field-level evidence on this profile rather than inferred from a generic capability list.'
                            : 'These are reviewed taxonomy mappings used for discovery and fit; field-level evidence may still be pending, so they are not presented as independently verified claims.'),
                    $displayFeatures->isNotEmpty()
                        ? ($verifiedFeatures->isNotEmpty()
                            ? 'Supporting verified capabilities include '.$displayFeatures->pluck('name')->take(4)->join(', ', ' and ').'.'
                            : 'Supporting cataloged capabilities include '.$displayFeatures->pluck('name')->take(4)->join(', ', ' and ').'.')
                        : null,
                ]),
            ]);
        }

        $technical = $tool->technicalProfile;

        // 4) API availability — only when the technical profile is source-backed and verified.
        if ($technical && $this->hasVerifiedTechnicalSource($technical->api_source_id, $verifiedSources)
            && in_array($technical->api_status, ['available', 'limited', 'unavailable'], true)) {
            $apiLead = match ($technical->api_status) {
                'available' => 'Yes. AI Orbit has verified official evidence that '.$tool->name.' provides API access.',
                'limited' => 'AI Orbit has verified that '.$tool->name.' provides limited or selected API access rather than unrestricted general API availability.',
                'unavailable' => 'AI Orbit has verified that a public API is not currently available for '.$tool->name.'.',
            };

            $faq->push([
                'q' => 'Does '.$tool->name.' have an API?',
                'a' => $this->composeAnswer([
                    $apiLead,
                    $technical->api_docs_url
                        ? 'The profile also records official API documentation, so developers can check supported endpoints, authentication and current implementation details at the provider source.'
                        : 'The API status is tied to a verified provider source on this profile and is not inferred from the product having integrations.',
                    'API availability can change independently from the consumer product, so production implementations should still be checked against the provider documentation.',
                ]),
            ]);
        }

        // 5) Deployment / self-hosting.
        if ($technical && $this->hasVerifiedTechnicalSource($technical->deployment_source_id, $verifiedSources)
            && in_array($technical->self_hosting_status, ['supported', 'enterprise_only', 'unsupported'], true)) {
            $modes = collect($technical->deployment_modes ?? [])->filter()->take(5)->values();
            $selfHostLead = match ($technical->self_hosting_status) {
                'supported' => 'Yes. Verified deployment evidence shows that '.$tool->name.' supports self-hosting.',
                'enterprise_only' => 'Self-hosted or private deployment for '.$tool->name.' is available only through enterprise-oriented deployment options according to the verified provider evidence.',
                'unsupported' => 'No. Verified deployment evidence indicates that '.$tool->name.' does not currently support self-hosting.',
            };

            $faq->push([
                'q' => 'Can '.$tool->name.' be self-hosted?',
                'a' => $this->composeAnswer([
                    $selfHostLead,
                    $modes->isNotEmpty() ? 'Documented deployment modes include '.$modes->join(', ', ' and ').'.' : null,
                    'Deployment rights, infrastructure requirements and enterprise restrictions should be checked against the provider documentation before production use.',
                ]),
            ]);
        }

        // 6) Open-source / source-available status with license distinction.
        if ($technical && $this->hasVerifiedTechnicalSource($technical->repository_source_id, $verifiedSources)
            && in_array($technical->open_source_status, ['open_source', 'source_available', 'mixed', 'proprietary'], true)) {
            $openSourceLead = match ($technical->open_source_status) {
                'open_source' => 'Yes. AI Orbit has verified '.$tool->name.' as open source from its official repository or license evidence.',
                'source_available' => $tool->name.' is source-available rather than being classified as fully open source in this profile.',
                'mixed' => $tool->name.' uses a mixed model that combines open or source-available components with proprietary components.',
                'proprietary' => 'No. AI Orbit currently classifies '.$tool->name.' as proprietary based on verified repository or license evidence.',
            };

            $faq->push([
                'q' => 'Is '.$tool->name.' open source?',
                'a' => $this->composeAnswer([
                    $openSourceLead,
                    $technical->license_name ? 'The recorded license information is '.$technical->license_name.'.' : null,
                    $technical->repository_url
                        ? 'An official repository is recorded on the profile, which should be used to review the exact license terms and current source availability.'
                        : 'The classification is based on the verified source linked to the technical profile, not on a marketing tag.',
                ]),
            ]);
        }

        // 7) Named integrations — only include individually verified pivot rows.
        $verifiedIntegrations = $tool->integrationTerms
            ->filter(function ($integration) use ($verifiedSources) {
                $pivot = $integration->pivot;
                return $pivot
                    && $pivot->verification_status === 'verified'
                    && $pivot->tool_source_id
                    && $verifiedSources->has((int) $pivot->tool_source_id);
            })
            ->values();

        if ($verifiedIntegrations->isNotEmpty()) {
            $integrationNames = $verifiedIntegrations->pluck('name')->filter()->take(6)->values();
            $faq->push([
                'q' => 'What integrations does '.$tool->name.' support?',
                'a' => $this->composeAnswer([
                    'Verified integration evidence on AI Orbit currently includes '.$integrationNames->join(', ', ' and ').'.',
                    'These are individually source-backed integration records, not a generic assumption based on the product simply advertising integrations.',
                    'The provider may support additional integrations that have not yet been individually verified in this profile.',
                ]),
            ]);
        }

        // 8) Privacy + security — only when the corresponding official source is verified.
        $privacyVerified = $technical && $this->hasVerifiedTechnicalSource($technical->privacy_source_id, $verifiedSources);
        $securityVerified = $technical && $this->hasVerifiedTechnicalSource($technical->security_source_id, $verifiedSources);
        if ($privacyVerified || $securityVerified) {
            $privacyParts = [];
            if ($privacyVerified && $technical->privacy_summary) {
                $privacyParts[] = $this->sentenceExcerpt($technical->privacy_summary, 2, 330);
            }
            if ($privacyVerified && $technical->data_training_policy && $technical->data_training_policy !== 'unknown') {
                $trainingLabel = ToolTechnicalProfile::TRAINING_POLICIES[$technical->data_training_policy] ?? null;
                if ($trainingLabel) {
                    $privacyParts[] = 'AI Orbit records the data-training policy as: '.$trainingLabel.'.';
                }
            }
            if ($securityVerified && $technical->security_summary) {
                $privacyParts[] = $this->sentenceExcerpt($technical->security_summary, 2, 330);
            }
            $certifications = collect($technical?->security_certifications ?? [])->filter()->take(5)->values();
            if ($securityVerified && $certifications->isNotEmpty()) {
                $privacyParts[] = 'Verified security certifications listed on the profile include '.$certifications->join(', ', ' and ').'.';
            }
            $privacyParts[] = 'These statements reflect the verified provider evidence stored by AI Orbit; policies and certifications can change, so sensitive deployments should re-check the current official documentation.';

            $faq->push([
                'q' => 'How does '.$tool->name.' handle privacy and security?',
                'a' => $this->composeAnswer($privacyParts),
            ]);
        }

        // 9) Pricing — use verified detailed pricing plans, never the legacy pricing_models cache.
        $verifiedPlans = $tool->pricingPlans
            ->filter(fn ($plan) => $plan->last_verified_at !== null)
            ->values();
        if ($verifiedPlans->isNotEmpty()) {
            $planNames = $verifiedPlans->pluck('plan_name')->filter()->unique()->take(4)->values();
            $faq->push([
                'q' => 'How much does '.$tool->name.' cost?',
                'a' => $this->composeAnswer([
                    'AI Orbit currently has verified plan-level pricing data for '.$tool->name.($planNames->isNotEmpty() ? ', including '.$planNames->join(', ', ' and ') : '').'.',
                    'Exact rates, billing units, limits and verification dates are shown in the pricing section of this profile rather than compressed into a single potentially misleading price.',
                    'Because providers can change prices and plan limits, check the linked official pricing source before making a purchase or production decision.',
                ]),
            ]);
        }

        return [
            'title' => $title,
            'description' => $description,
            'faq' => $faq->filter(fn ($item) => trim((string) ($item['a'] ?? '')) !== '')->take(9)->values()->all(),
            'caps' => $displayFeatures->pluck('name')->filter()->take(5)->values(),
            'platforms' => $platforms,
        ];
    }

    public function model(AiModel $model): array
    {
        $model->loadMissing(['company', 'tool.category', 'featureTerms', 'useCaseTerms', 'pricingSources', 'benchmarkResults.benchmark']);

        $company = $model->company?->name ?? 'its provider';
        $legacyCaps = collect($model->capabilities ?? [])->filter()->values();
        $taxonomyCaps = $model->featureTerms->pluck('name')->filter()->values();
        $caps = ($taxonomyCaps->isNotEmpty() ? $taxonomyCaps : $legacyCaps)->unique()->take(5)->values();

        $titleBase = $model->name.($model->company ? ' by '.$company : '').' — Specs, Pricing & Benchmarks';
        $title = Str::limit($titleBase, 66, '');
        $parts = ['Explore '.$model->name.' by '.$company];
        if ($model->version) $parts[] = 'version '.$model->version;
        if ($model->context_window) $parts[] = 'context window '.$model->context_window;
        if ($caps->isNotEmpty()) $parts[] = 'capabilities including '.$caps->take(3)->join(', ');
        if ($model->benchmark_score !== null) $parts[] = 'benchmark profile and verified performance data';
        if ($model->input_price_per_million !== null || $model->output_price_per_million !== null) {
            $parts[] = 'API token pricing';
        } elseif (filled($model->pricing_type)) {
            $parts[] = $model->pricing_type_label.' and commercial terms';
        }
        $parts[] = 'related models and provider information';
        $description = $this->normalizeText(Str::limit(implode('. ', $parts).'.', 158, ''));
        $title = $this->normalizeText($title);

        $overviewAnswer = $this->normalizeText($model->overview ?: $model->capability_notes);
        if ($overviewAnswer === '') {
            $overviewAnswer = $model->name.' is an AI model from '.$company.'.';
        }

        $useCases = $model->useCaseTerms->pluck('name')->filter()->unique()->take(5)->values();
        $pricingParts = collect([
            $model->input_price_per_million !== null ? '$'.number_format((float) $model->input_price_per_million, 2).' per 1M input tokens' : null,
            $model->output_price_per_million !== null ? '$'.number_format((float) $model->output_price_per_million, 2).' per 1M output tokens' : null,
        ])->filter()->values();

        $faq = [
            ['q' => 'What is '.$model->name.'?', 'a' => Str::limit($overviewAnswer, 360)],
            ['q' => 'Who created '.$model->name.'?', 'a' => $model->company ? $model->name.' is provided by '.$model->company->name.'.' : 'The provider is not currently listed in AI Orbit.'],
            ['q' => 'What can '.$model->name.' do?', 'a' => $caps->isNotEmpty() ? 'Its listed capabilities include '.$caps->join(', ').'.' : 'Capability details are shown on this model profile when verified data is available.'],
            ['q' => 'What is '.$model->name.' useful for?', 'a' => $useCases->isNotEmpty() ? 'AI Orbit currently maps '.$model->name.' to use cases including '.$useCases->join(', ', ' and ').'.' : 'Structured use-case mappings have not yet been added to this profile.'],
            ['q' => 'What is the context window of '.$model->name.'?', 'a' => $model->context_window ? 'AI Orbit currently lists the context window as '.$model->context_window.'.' : 'A verified context-window value is not currently listed.'],
            ['q' => 'How much does '.$model->name.' cost?', 'a' => $pricingParts->isNotEmpty()
                ? 'AI Orbit currently lists '.$pricingParts->join(' and ').'. Provider pricing can change, so check the linked official pricing source before production use.'
                : (filled($model->pricing_basis) || filled($model->pricing_summary)
                    ? trim('AI Orbit classifies the pricing structure as '.$model->pricing_type_label.'. '.($model->pricing_basis ? 'Verified basis: '.$model->pricing_basis.'. ' : '').($model->pricing_summary ?: '')).' Check the linked official provider source for current production terms.'
                    : ($model->pricingSources->isNotEmpty()
                        ? 'AI Orbit monitors official pricing sources for this model, but a current generic token price is not displayed. Check the provider source for the applicable rates.'
                        : 'Verified commercial pricing is not currently listed on this profile; check the provider for current terms.'))],
        ];

        return compact('title','description','faq','caps');
    }

    public function schemas(string $kind, object $entity, array $seo): array
    {
        $url = $kind === 'tool' ? route('tools.show', $entity) : route('models.show', $entity);

        $breadcrumbItems = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $kind === 'tool' ? 'AI Tools' : 'AI Models',
                'item' => $kind === 'tool' ? route('tools.index') : route('models.index'),
            ],
        ];

        if ($kind === 'tool') {
            $entity->loadMissing(['company', 'category', 'subcategoryTerm']);

            if ($entity->category && $entity->category->is_active) {
                $breadcrumbItems[] = [
                    '@type' => 'ListItem',
                    'position' => count($breadcrumbItems) + 1,
                    'name' => $entity->category->name,
                    'item' => route('categories.show', $entity->category),
                ];
            }

            if ($entity->subcategoryTerm && $entity->subcategoryTerm->is_active && $entity->category) {
                $breadcrumbItems[] = [
                    '@type' => 'ListItem',
                    'position' => count($breadcrumbItems) + 1,
                    'name' => $entity->subcategoryTerm->name,
                    'item' => route('categories.subcategories.show', [$entity->category, $entity->subcategoryTerm]),
                ];
            }
        } elseif ($entity->company && in_array($entity->company->status, ['active', 'acquired'], true)) {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => count($breadcrumbItems) + 1,
                'name' => $entity->company->name,
                'item' => route('companies.show', $entity->company),
            ];
        }

        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => count($breadcrumbItems) + 1,
            'name' => $entity->name,
            'item' => $url,
        ];

        $breadcrumb = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ];

        $faq = [
            '@type' => 'FAQPage',
            'mainEntity' => collect($seo['faq'])->map(fn ($item) => [
                '@type' => 'Question',
                'name' => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ])->values()->all(),
        ];

        if ($kind === 'tool') {
            $main = [
                '@type' => 'SoftwareApplication',
                '@id' => $url.'#software',
                'name' => $entity->name,
                'url' => $url,
                'description' => $seo['description'],
                'applicationCategory' => $entity->category?->name ?: 'Artificial Intelligence',
                'operatingSystem' => collect($entity->platforms ?? [])->filter()->join(', ') ?: 'Web',
                'image' => $this->absoluteUrl($entity->logo_url),
            ];

            if ($entity->company && in_array($entity->company->status, ['active', 'acquired'], true)) {
                $main['author'] = [
                    '@type' => 'Organization',
                    'name' => $entity->company->name,
                    'url' => route('companies.show', $entity->company),
                ];
            }

            if ((float) $entity->rating > 0 && $entity->reviews->count() > 0) {
                $main['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => (float) $entity->rating,
                    'bestRating' => 5,
                    'ratingCount' => $entity->reviews->count(),
                ];
            }
        } else {
            $modelEntity = array_filter([
                '@type' => 'Thing',
                '@id' => $url.'#ai-model',
                'name' => $entity->name,
                'description' => $entity->capability_notes ?: $seo['description'],
                'image' => $this->absoluteUrl($entity->logo_url),
            ], fn ($value) => $value !== null && $value !== '');

            $mentions = [];

            if ($entity->company && in_array($entity->company->status, ['active', 'acquired'], true)) {
                $mentions[] = [
                    '@type' => 'Organization',
                    'name' => $entity->company->name,
                    'url' => route('companies.show', $entity->company),
                ];
            }

            if ($entity->tool && $entity->tool->status === 'published') {
                $mentions[] = [
                    '@type' => 'SoftwareApplication',
                    'name' => $entity->tool->name,
                    'url' => route('tools.show', $entity->tool),
                ];
            }

            $main = array_filter([
                '@type' => 'WebPage',
                '@id' => $url.'#webpage',
                'name' => $seo['title'],
                'url' => $url,
                'description' => $seo['description'],
                'dateModified' => $entity->updated_at?->toAtomString(),
                'mainEntity' => $modelEntity,
                'mentions' => $mentions ?: null,
                'isPartOf' => [
                    '@type' => 'WebSite',
                    'name' => 'AI Orbit',
                    'url' => route('home'),
                ],
            ], fn ($value) => $value !== null && $value !== '');
        }

        return [$main, $breadcrumb, $faq];
    }

    private function hasVerifiedTechnicalSource(?int $sourceId, $verifiedSources): bool
    {
        return $sourceId !== null && $verifiedSources->has((int) $sourceId);
    }

    private function composeAnswer(array $parts): string
    {
        $sentences = collect($parts)
            ->flatten()
            ->map(fn ($part) => $this->normalizeText((string) $part))
            ->filter()
            ->values();

        if ($sentences->isEmpty()) {
            return '';
        }

        return Str::limit($sentences->take(4)->join(' '), 760, '');
    }

    private function sentenceExcerpt(?string $value, int $maxSentences = 2, int $maxChars = 320): ?string
    {
        $text = $this->normalizeText($value);
        if ($text === '') {
            return null;
        }

        $sentences = preg_split('/(?<=[.!?])\\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $excerpt = trim(implode(' ', array_slice($sentences, 0, max(1, $maxSentences))));

        if ($excerpt === '') {
            $excerpt = $text;
        }

        return Str::limit($excerpt, $maxChars, '');
    }

    private function joinSentenceParts(array $parts): ?string
    {
        $parts = collect($parts)->filter()->values();
        if ($parts->isEmpty()) {
            return null;
        }

        return $parts->join(' and ').'.';
    }

    private function normalizeText(?string $value): string
    {
        $text = trim((string) $value);

        for ($i = 0; $i < 5; $i++) {
            $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $text) {
                break;
            }
            $text = $decoded;
        }

        return trim(strip_tags($text));
    }

    private function absoluteUrl(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        return url('/'.ltrim($value, '/'));
    }
}
