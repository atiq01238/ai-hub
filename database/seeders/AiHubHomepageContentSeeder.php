<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\Benchmark;
use App\Models\BenchmarkResult;
use App\Models\Company;
use App\Models\PricingPlan;
use App\Models\PricingHistory;
use App\Models\Review;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AiHubHomepageContentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );

        $this->seedPricing();
        $this->seedPricingHistory();
        $this->seedReviews($user);
        $this->seedArticles($user);
        $this->seedBenchmarks();
    }

    private function seedPricing(): void
    {
        $plans = [
            ['ChatGPT', 'Free', 0, 0, null, 'Core access', 'Usage limits apply'],
            ['ChatGPT', 'Plus', 20, 200, null, 'Higher limits', 'Priority model access'],
            ['Claude', 'Free', 0, 0, null, 'Core chat access', 'Usage limits apply'],
            ['Claude', 'Pro', 20, 200, null, 'Higher limits', 'Priority access'],
            ['Gemini', 'Free', 0, 0, null, 'Core access', 'Usage limits apply'],
            ['Gemini', 'Advanced', 19.99, 199.99, null, 'Premium access', 'Expanded limits'],
            ['Midjourney', 'Basic', 10, 96, null, 'Image generation', 'Starter GPU time'],
            ['Runway', 'Standard', 15, 144, null, 'Video generation', 'Monthly credits'],
            ['Perplexity', 'Pro', 20, 200, null, 'Pro searches', 'Higher research limits'],
            ['ElevenLabs', 'Starter', 5, 50, null, 'Voice credits', 'Monthly character limits'],
            ['GitHub Copilot', 'Pro', 10, 100, null, 'Coding assistant', 'Individual plan'],
            ['Adobe Firefly', 'Standard', 9.99, 99.99, null, 'Generative credits', 'Monthly credit limit'],
        ];

        foreach ($plans as [$toolName, $planName, $monthly, $yearly, $api, $credits, $limits]) {
            $tool = Tool::where('name', $toolName)->first();
            if (!$tool) continue;

            PricingPlan::updateOrCreate(
                ['tool_id' => $tool->id, 'plan_name' => $planName],
                [
                    'monthly_price' => $monthly,
                    'yearly_price' => $yearly,
                    'api_price_label' => $api,
                    'credits' => $credits,
                    'limits' => $limits,
                ]
            );
        }
    }

    private function seedPricingHistory(): void
    {
        $rows = [
            ['ChatGPT','Plus',18,20,'increase',12],
            ['Claude','Pro',22,20,'decrease',10],
            ['Midjourney','Basic',8,10,'increase',8],
            ['Runway','Standard',12,15,'increase',6],
            ['Perplexity','Pro',18,20,'increase',4],
        ];
        foreach ($rows as [$toolName,$plan,$old,$new,$type,$days]) {
            $tool = Tool::where('name',$toolName)->first();
            if (!$tool) continue;
            PricingHistory::firstOrCreate(
                ['tool_id'=>$tool->id,'plan_name'=>$plan,'old_price'=>$old,'new_price'=>$new],
                ['metric'=>'monthly_price','old_value'=>(string)$old,'new_value'=>(string)$new,'change_type'=>$type,'created_at'=>now()->subDays($days),'updated_at'=>now()->subDays($days)]
            );
        }
    }

    private function seedReviews(User $user): void
    {
        $rows = [
            ['ChatGPT', 4.9, 'Best all-round AI assistant', 'Excellent balance of reasoning, multimodal workflows and everyday usability.'],
            ['Claude', 4.8, 'Excellent for long-form reasoning', 'Strong writing quality, careful analysis and a clean workflow for documents.'],
            ['Gemini', 4.7, 'Powerful multimodal ecosystem', 'A strong option for users already working across Google products and research tasks.'],
            ['Midjourney', 4.8, 'Outstanding visual quality', 'Still one of the strongest creative image tools for polished visual output.'],
            ['Runway', 4.6, 'A serious AI video workspace', 'Useful generation and editing tools make it a compelling creative production platform.'],
            ['Perplexity', 4.7, 'Fast research with citations', 'A practical research assistant when source visibility matters.'],
        ];

        foreach ($rows as [$toolName, $rating, $verdict, $body]) {
            $tool = Tool::where('name', $toolName)->first();
            if (!$tool) continue;

            Review::updateOrCreate(
                ['tool_id' => $tool->id, 'user_id' => $user->id, 'review_type' => 'editorial'],
                [
                    'rating' => $rating,
                    'verdict' => $verdict,
                    'body' => $body,
                    'pros' => ['Strong feature set', 'Polished user experience'],
                    'cons' => ['Premium limits may apply'],
                    'rating_breakdown' => ['quality' => $rating, 'ease_of_use' => max(4.2, $rating - .2), 'value' => max(4.1, $rating - .3)],
                    'status' => 'published',
                ]
            );
        }
    }

    private function seedArticles(User $user): void
    {
        $rows = [
            ['How to Choose the Right AI Assistant in 2026', 'Guide', 'ChatGPT', 'OpenAI', 'A practical framework for comparing reasoning, speed, multimodal features, pricing and workflow fit.'],
            ['AI Model Benchmarks: What the Scores Really Mean', 'Benchmark', 'Claude', 'Anthropic', 'Understand how benchmark results should be interpreted before choosing a model for real-world work.'],
            ['Best AI Image Tools for Creative Workflows', 'Guide', 'Midjourney', 'Midjourney', 'A concise guide to image generators, creative control, output quality and pricing trade-offs.'],
            ['AI Video Generation Is Moving Fast — Here Is What Matters', 'Product Update', 'Runway', 'Runway', 'A useful look at quality, consistency, editing controls and the practical limits of current AI video tools.'],
            ['Free vs Paid AI Tools: When Is an Upgrade Worth It?', 'Pricing Change', 'Gemini', 'Google', 'Compare free plans, premium limits and the features that usually justify paying for an AI product.'],
            ['Building a Better AI Research Workflow', 'Research', 'Perplexity', 'Perplexity', 'How to combine source-grounded search, document analysis and model comparison for more reliable research.'],
        ];

        foreach ($rows as $index => [$title, $category, $toolName, $companyName, $summary]) {
            $tool = Tool::where('name', $toolName)->first();
            $company = Company::where('name', $companyName)->first();
            $slug = Str::slug($title);

            Article::updateOrCreate(
                ['slug' => $slug],
                [
                    'user_id' => $user->id,
                    'company_id' => $company?->id,
                    'title' => $title,
                    'featured_image_path' => $tool?->cover_image_path,
                    'content' => $summary.' This seeded article exists to validate the AI Hub public homepage, content cards and article workflow.',
                    'summary' => $summary,
                    'category' => $category,
                    'tags' => ['AI', $category, $toolName],
                    'related_tools' => $tool ? [$tool->id] : [],
                    'related_models' => [],
                    'seo_title' => $title.' | AI Hub',
                    'meta_description' => $summary,
                    'status' => 'published',
                    'approval_status' => 'approved',
                    'published_at' => now()->subDays($index + 1),
                    'approved_at' => now()->subDays($index + 2),
                ]
            );
        }
    }

    private function seedBenchmarks(): void
    {
        $benchmarkScores = [
            'MMLU Pro' => ['GPT-5.6 Sol' => 96.8, 'Claude Opus 5' => 96.1, 'Gemini 3.6 Flash' => 94.9],
            'HumanEval' => ['GPT-5.6 Sol' => 97.2, 'Claude Sonnet 5' => 96.4, 'Gemini 3.6 Flash' => 95.0],
            'GPQA Diamond' => ['Claude Opus 5' => 96.5, 'GPT-5.6 Sol' => 96.2, 'Gemini 3.6 Flash' => 94.4],
            'SWE-bench' => ['Claude Sonnet 5' => 95.8, 'GPT-5.6 Sol' => 95.3, 'Mistral Medium 3.5' => 90.8],
        ];

        foreach ($benchmarkScores as $benchmarkName => $models) {
            $benchmark = Benchmark::firstOrCreate(
                ['name' => $benchmarkName],
                [
                    'slug' => Str::slug($benchmarkName),
                    'category' => 'General',
                    'weight' => 1,
                    'max_score' => 100,
                    'higher_is_better' => true,
                    'is_active' => true,
                ]
            );

            foreach ($models as $modelName => $score) {
                $model = AiModel::where('name', $modelName)->first();
                if (!$model) continue;

                BenchmarkResult::updateOrCreate(
                    [
                        'benchmark_id' => $benchmark->id,
                        'benchmarkable_type' => AiModel::class,
                        'benchmarkable_id' => $model->id,
                    ],
                    [
                        'score' => $score,
                        'tested_at' => now()->subDays(7)->toDateString(),
                        'source_name' => 'AI Hub seeded validation dataset',
                        'notes' => 'Seeded benchmark result for public UI validation.',
                        'verified' => true,
                    ]
                );
            }
        }


        // Public benchmark explorer also needs tool-level coverage. These scores are
        // derived from the structured benchmark snapshot already stored on seeded tools.
        foreach (Tool::query()->where('status', 'published')->get() as $tool) {
            foreach (($tool->benchmarks ?? []) as $benchmarkName => $score) {
                if (!is_numeric($score)) continue;

                $benchmark = Benchmark::firstOrCreate(
                    ['name' => $benchmarkName],
                    [
                        'slug' => Str::slug($benchmarkName),
                        'category' => 'Product Evaluation',
                        'description' => 'AI Hub product-level evaluation metric for public tool comparison.',
                        'weight' => 1,
                        'max_score' => 100,
                        'higher_is_better' => true,
                        'is_active' => true,
                    ]
                );

                BenchmarkResult::updateOrCreate(
                    [
                        'benchmark_id' => $benchmark->id,
                        'benchmarkable_type' => Tool::class,
                        'benchmarkable_id' => $tool->id,
                    ],
                    [
                        'score' => (float) $score,
                        'tested_at' => now()->subDays(5)->toDateString(),
                        'source_name' => 'AI Hub seeded product evaluation',
                        'notes' => 'Seeded from the tool benchmark snapshot for frontend validation.',
                        'verified' => true,
                    ]
                );
            }
        }
    }
}
