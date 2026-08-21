<?php

namespace App\Services\Seo;

use App\Models\Company;
use Illuminate\Support\Collection;

class CompanyContentService
{
    public function build(Company $company, Collection $tools, Collection $models, Collection $news, Collection $articles): array
    {
        $toolNames = $tools->pluck('name')->filter()->values();
        $modelNames = $models->pluck('name')->filter()->values();
        $categories = $tools->pluck('category.name')->filter()->countBy()->sortDesc()->keys()->take(5)->values();

        $toolCount = (int) ($company->published_tools_count ?? $tools->count());
        $modelCount = (int) ($company->active_models_count ?? $models->count());
        $newsCount = (int) ($company->published_news_count ?? $news->count());

        $intro = trim(strip_tags((string) $company->description));
        if ($intro === '') {
            $intro = $company->name.' is an artificial intelligence company tracked by AI Hub for its models, products and industry activity.';
        }

        $portfolioSummary = $this->portfolioSummary($company->name, $toolCount, $modelCount, $toolNames, $modelNames);
        $focusSummary = $categories->isNotEmpty()
            ? $company->name.' is currently represented in AI Hub across '.$this->naturalList($categories->all()).'.'
            : null;

        $modelSummary = null;
        if ($modelNames->isNotEmpty()) {
            $modelSummary = $company->name.' model coverage includes '.$this->naturalList($modelNames->take(5)->all())
                .($modelCount > 5 ? ', with additional models available in the full model directory.' : '.');
        }

        $toolSummary = null;
        if ($toolNames->isNotEmpty()) {
            $toolSummary = $company->name.' product coverage includes '.$this->naturalList($toolNames->take(5)->all())
                .($toolCount > 5 ? ', with additional products available in the AI tools directory.' : '.');
        }

        $latestSignal = $news->first();
        $latestNewsSummary = $latestSignal
            ? 'The latest published '.$company->name.' intelligence item tracked by AI Hub is “'.\Illuminate\Support\Str::limit($latestSignal->headline, 110, '…').'”.'
            : ($newsCount === 0 ? 'No published '.$company->name.' news items are currently linked to this profile.' : null);

        $facts = collect([
            $company->founded_year ? ['label' => 'Founded', 'value' => (string) $company->founded_year] : null,
            ['label' => 'AI models tracked', 'value' => (string) $modelCount],
            ['label' => 'AI tools tracked', 'value' => (string) $toolCount],
            ['label' => 'News signals', 'value' => (string) $newsCount],
            $categories->isNotEmpty() ? ['label' => 'Primary AI focus', 'value' => $categories->first()] : null,
        ])->filter()->values();

        $faq = [];
        $faq[] = [
            'question' => 'What does '.$company->name.' do?',
            'answer' => $intro.' '.$portfolioSummary,
        ];

        if ($modelNames->isNotEmpty()) {
            $faq[] = [
                'question' => 'Which AI models are associated with '.$company->name.'?',
                'answer' => $modelSummary.' Model availability, status and specifications can change as new releases are added.',
            ];
        }

        if ($toolNames->isNotEmpty()) {
            $faq[] = [
                'question' => 'Which AI tools or products from '.$company->name.' are listed on AI Hub?',
                'answer' => $toolSummary.' Each linked product has its own profile with available features and product information.',
            ];
        }

        if ($company->founded_year) {
            $faq[] = [
                'question' => 'When was '.$company->name.' founded?',
                'answer' => $company->name.' is listed in AI Hub as founded in '.$company->founded_year.'.',
            ];
        }

        $faq[] = [
            'question' => 'Where can I follow '.$company->name.' models, tools and news?',
            'answer' => 'This AI Hub company profile connects '.$company->name.' with its linked AI models, tools, published news and research so you can move between related records from one page.',
        ];

        return [
            'intro' => $intro,
            'portfolio_summary' => $portfolioSummary,
            'model_summary' => $modelSummary,
            'tool_summary' => $toolSummary,
            'focus_summary' => $focusSummary,
            'latest_news_summary' => $latestNewsSummary,
            'categories' => $categories,
            'model_names' => $modelNames->take(6),
            'tool_names' => $toolNames->take(6),
            'facts' => $facts,
            'faq' => collect($faq)->filter(fn ($item) => trim($item['answer'] ?? '') !== '')->values(),
        ];
    }

    private function portfolioSummary(string $companyName, int $toolCount, int $modelCount, Collection $toolNames, Collection $modelNames): string
    {
        $parts = [];
        if ($modelCount > 0) $parts[] = $modelCount.' AI model'.($modelCount === 1 ? '' : 's');
        if ($toolCount > 0) $parts[] = $toolCount.' AI tool'.($toolCount === 1 ? '' : 's');

        if ($parts === []) {
            return 'AI Hub does not yet have published model or tool records linked to this company.';
        }

        $sentence = 'On AI Hub, '.$companyName.' is currently connected to '.$this->naturalList($parts).'.';

        $examples = [];
        if ($modelNames->isNotEmpty()) $examples[] = 'models such as '.$this->naturalList($modelNames->take(3)->all());
        if ($toolNames->isNotEmpty()) $examples[] = 'products such as '.$this->naturalList($toolNames->take(3)->all());

        if ($examples) $sentence .= ' The directory includes '.$this->naturalList($examples).'.';
        return $sentence;
    }

    private function naturalList(array $values): string
    {
        $values = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $values)));
        $count = count($values);
        if ($count === 0) return '';
        if ($count === 1) return $values[0];
        if ($count === 2) return $values[0].' and '.$values[1];
        return implode(', ', array_slice($values, 0, -1)).', and '.$values[$count - 1];
    }
}
