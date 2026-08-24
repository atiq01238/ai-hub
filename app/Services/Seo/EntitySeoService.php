<?php

namespace App\Services\Seo;

use App\Models\AiModel;
use App\Models\Tool;
use Illuminate\Support\Str;

class EntitySeoService
{
    public function tool(Tool $tool): array
    {
        $company = $tool->company?->name;
        $category = $tool->category?->name;
        $pricing = collect($tool->pricing_models ?? [])->filter()->take(2)->join(' & ');
        $caps = collect($tool->capabilities ?? [])->filter()->take(4)->values();
        $platforms = collect($tool->platforms ?? [])->filter()->take(3)->values();

        $title = $tool->seo_title ?: $tool->name.' Review: Features, Pricing, Alternatives & AI Models';
        $description = $tool->meta_description ?: Str::limit(trim(
            'Explore '.$tool->name.($company ? ' by '.$company : '').
            ($category ? ', a '.$category.' AI tool' : ' AI tool').
            '. See features'.($pricing ? ', '.$pricing.' pricing' : ', pricing').
            ', supported platforms, linked AI models, benchmarks, reviews and alternatives.'
        ), 158, '');

        $faq = [
            ['q' => 'What is '.$tool->name.'?', 'a' => Str::limit(strip_tags($tool->description ?: $tool->short_description ?: $tool->name.' is an AI tool listed in the AI Orbit directory.'), 360)],
            ['q' => 'Who makes '.$tool->name.'?', 'a' => $company ? $tool->name.' is associated with '.$company.' in the AI Orbit directory.' : 'AI Orbit currently lists '.$tool->name.' as an independent AI product.'],
            ['q' => 'What is '.$tool->name.' best for?', 'a' => $caps->isNotEmpty() ? $tool->name.' is listed for capabilities including '.$caps->join(', ').'.' : 'Its current use cases are described in the features and overview sections on this page.'],
            ['q' => 'How much does '.$tool->name.' cost?', 'a' => $pricing ? 'AI Orbit currently classifies '.$tool->name.' pricing as '.$pricing.'. Check the pricing section and official provider site for current rates.' : 'Detailed pricing may change; use the pricing section and official provider site for the latest rates.'],
        ];

        return compact('title','description','faq','caps','platforms');
    }

    public function model(AiModel $model): array
    {
        $company = $model->company?->name ?? 'its provider';
        $caps = collect($model->capabilities ?? [])->filter()->take(5)->values();
        $title = $model->name.' AI Model: Specs, Pricing, Benchmarks & Capabilities';
        $parts = ['Explore '.$model->name.' by '.$company];
        if ($model->context_window) $parts[] = 'context window '.$model->context_window;
        if ($caps->isNotEmpty()) $parts[] = 'capabilities including '.$caps->take(3)->join(', ');
        $parts[] = 'API pricing, benchmark results, related models and provider information';
        $description = Str::limit(implode('. ', $parts).'.', 158, '');

        $faq = [
            ['q' => 'What is '.$model->name.'?', 'a' => Str::limit(strip_tags($model->capability_notes ?: $model->name.' is an AI model from '.$company.'.'), 360)],
            ['q' => 'Who created '.$model->name.'?', 'a' => $model->company ? $model->name.' is provided by '.$model->company->name.'.' : 'The provider is not currently listed in AI Orbit.'],
            ['q' => 'What can '.$model->name.' do?', 'a' => $caps->isNotEmpty() ? 'Its listed capabilities include '.$caps->join(', ').'.' : 'Capability details are shown on this model profile when verified data is available.'],
            ['q' => 'What is the context window of '.$model->name.'?', 'a' => $model->context_window ? 'AI Orbit currently lists the context window as '.$model->context_window.'.' : 'A verified context-window value is not currently listed.'],
            ['q' => 'How much does '.$model->name.' cost?', 'a' => ($model->input_price_per_million !== null || $model->output_price_per_million !== null)
                ? 'The profile lists token pricing where available. Current values should be checked against the provider before production use.'
                : 'Verified token pricing is not currently listed on this profile; check the provider for current rates.'],
        ];

        return compact('title','description','faq','caps');
    }

    public function schemas(string $kind, object $entity, array $seo): array
    {
        $url = $kind === 'tool' ? route('tools.show',$entity) : route('models.show',$entity);
        $breadcrumb = [
            '@type'=>'BreadcrumbList',
            'itemListElement'=>[
                ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>route('home')],
                ['@type'=>'ListItem','position'=>2,'name'=>$kind === 'tool' ? 'AI Tools' : 'AI Models','item'=>$kind === 'tool' ? route('tools.index') : route('models.index')],
                ['@type'=>'ListItem','position'=>3,'name'=>$entity->name,'item'=>$url],
            ],
        ];
        $faq = [
            '@type'=>'FAQPage',
            'mainEntity'=>collect($seo['faq'])->map(fn($item)=>[
                '@type'=>'Question','name'=>$item['q'],
                'acceptedAnswer'=>['@type'=>'Answer','text'=>$item['a']],
            ])->values()->all(),
        ];

        if ($kind === 'tool') {
            $main = [
                '@type'=>'SoftwareApplication',
                '@id'=>$url.'#software',
                'name'=>$entity->name,
                'url'=>$url,
                'description'=>$seo['description'],
                'applicationCategory'=>$entity->category?->name ?: 'Artificial Intelligence',
                'operatingSystem'=>collect($entity->platforms ?? [])->filter()->join(', ') ?: 'Web',
                'image'=>$entity->logo_url,
            ];
            if ($entity->company) $main['author']=['@type'=>'Organization','name'=>$entity->company->name,'url'=>route('companies.show',$entity->company)];
            if ((float)$entity->rating > 0 && $entity->reviews->count() > 0) $main['aggregateRating']=['@type'=>'AggregateRating','ratingValue'=>(float)$entity->rating,'bestRating'=>5,'ratingCount'=>$entity->reviews->count()];
        } else {
            $main = [
                '@type'=>'TechArticle',
                '@id'=>$url.'#profile',
                'headline'=>$seo['title'],
                'name'=>$entity->name,
                'url'=>$url,
                'description'=>$seo['description'],
                'image'=>$entity->logo_url,
                'about'=>[
                    '@type'=>'Thing',
                    'name'=>$entity->name,
                    'description'=>$entity->capability_notes ?: $seo['description'],
                ],
            ];
            if ($entity->company) $main['author']=['@type'=>'Organization','name'=>$entity->company->name,'url'=>route('companies.show',$entity->company)];
            if ($entity->release_date) $main['datePublished']=$entity->release_date->toDateString();
            $main['dateModified']=$entity->updated_at?->toAtomString();
        }

        return [$main,$breadcrumb,$faq];
    }
}
