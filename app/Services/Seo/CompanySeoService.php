<?php

namespace App\Services\Seo;

use App\Models\Company;
use Illuminate\Support\Str;

class CompanySeoService
{
    public function build(Company $company, int $toolCount, int $modelCount, int $newsCount, $lastUpdated = null): array
    {
        $title = $company->name.' AI Company Profile: Models, Tools & Latest News | AI Hub';

        $base = trim(strip_tags((string) $company->description));
        if ($base === '') {
            $base = 'Research '.$company->name.' on AI Hub with linked AI models, tools, company information and industry updates.';
        }

        $signals = [];
        if ($modelCount > 0) $signals[] = $modelCount.' AI model'.($modelCount === 1 ? '' : 's');
        if ($toolCount > 0) $signals[] = $toolCount.' tool'.($toolCount === 1 ? '' : 's');
        if ($newsCount > 0) $signals[] = $newsCount.' news update'.($newsCount === 1 ? '' : 's');

        $description = Str::limit($base, 118, '');
        if ($signals) {
            $description = Str::limit($description.' AI Hub tracks '.implode(', ', $signals).'.', 158, '');
        } else {
            $description = Str::limit($description, 158, '');
        }

        $canonical = route('companies.show', $company);
        $logo = $company->logo_url;
        $updated = $lastUpdated ?: $company->updated_at;
        $organizationId = $canonical.'#organization';

        $organization = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $organizationId,
            'name' => $company->name,
            'url' => $company->website ?: $canonical,
            'mainEntityOfPage' => $canonical,
            'logo' => $logo,
            'description' => trim(strip_tags((string) $company->description)) ?: null,
            'foundingDate' => $company->founded_year ? (string) $company->founded_year : null,
            'sameAs' => $company->website ? [$company->website] : null,
        ], fn ($value) => $value !== null && $value !== '' && $value !== []);

        $webPage = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            '@id' => $canonical.'#profile',
            'name' => $title,
            'url' => $canonical,
            'description' => $description,
            'dateModified' => $updated?->toAtomString(),
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'AI Hub',
                'url' => route('home'),
            ],
            'mainEntity' => ['@id' => $organizationId],
        ], fn ($value) => $value !== null && $value !== '');

        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'AI Companies', 'item' => route('companies.index')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $company->name, 'item' => $canonical],
            ],
        ];

        return compact('title', 'description', 'canonical', 'logo', 'organization', 'webPage', 'breadcrumb');
    }
}
