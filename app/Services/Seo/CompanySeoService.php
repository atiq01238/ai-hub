<?php

namespace App\Services\Seo;

use App\Models\Company;
use Illuminate\Support\Str;

class CompanySeoService
{
    public function build(Company $company, int $toolCount, int $modelCount, int $newsCount, $lastUpdated = null): array
    {
        $title = $company->name.' AI Company Profile: Models, Tools & Latest News | AI Orbit';

        $companyDescription = $this->normalizeText($company->description);
        $base = $companyDescription;
        if ($base === '') {
            $base = 'Research '.$company->name.' on AI Orbit with linked AI models, tools, company information and industry updates.';
        }

        $signals = [];
        if ($modelCount > 0) $signals[] = $modelCount.' AI model'.($modelCount === 1 ? '' : 's');
        if ($toolCount > 0) $signals[] = $toolCount.' tool'.($toolCount === 1 ? '' : 's');
        if ($newsCount > 0) $signals[] = $newsCount.' news update'.($newsCount === 1 ? '' : 's');

        $description = Str::limit($base, 118, '');
        if ($signals) {
            $description = Str::limit($description.' AI Orbit tracks '.implode(', ', $signals).'.', 158, '');
        } else {
            $description = Str::limit($description, 158, '');
        }

        $canonical = route('companies.show', $company);
        $logo = $this->absoluteUrl($company->logo_url);
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
            'description' => $companyDescription ?: null,
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
                'name' => 'AI Orbit',
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
