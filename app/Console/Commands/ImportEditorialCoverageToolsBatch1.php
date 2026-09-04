<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleWorkflowEvent;
use App\Models\Category;
use App\Models\Comparison;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class ImportEditorialCoverageToolsBatch1 extends Command
{
    protected $signature = 'editorial:import-tools-b1
        {--dataset=tools-b1-v1-2026-09-04 : Dataset filename without extension}
        {--dry-run : Validate the package without writing anything}
        {--publish : Publish/approve articles and publish comparisons}
        {--refresh : Update rows when the same slug already exists}
        {--author= : Article author email; defaults to the first active admin/user}';

    protected $description = 'Import AI Orbit Editorial Coverage Batch 1: decision guides plus curated tool comparisons.';

    public function handle(): int
    {
        $dataset = trim((string) $this->option('dataset'));
        $path = database_path("data/editorial/{$dataset}.json");

        if ($dataset === '' || ! File::exists($path)) {
            $this->error("Editorial dataset not found: {$path}");
            return self::FAILURE;
        }

        try {
            $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->error('Invalid editorial dataset JSON: '.$e->getMessage());
            return self::FAILURE;
        }

        $articles = collect($payload['articles'] ?? []);
        $comparisons = collect($payload['comparisons'] ?? []);

        if ($articles->isEmpty() && $comparisons->isEmpty()) {
            $this->error('The editorial dataset contains no articles or comparisons.');
            return self::FAILURE;
        }

        $toolNames = $articles->pluck('tools')->flatten()
            ->merge($comparisons->pluck('items')->flatten())
            ->filter()->unique()->values();

        $tools = Tool::query()->whereIn('name', $toolNames)->get()->keyBy('name');
        $missingTools = $toolNames->reject(fn ($name) => $tools->has($name))->values();
        $nonPublicTools = $tools->filter(fn (Tool $tool) => $tool->status !== 'published')->keys()->values();

        $topicSlugs = $articles->pluck('topic')->filter()->unique()->values();
        $topics = Category::query()->content()->active()->whereIn('slug', $topicSlugs)->get()->keyBy('slug');
        $missingTopics = $topicSlugs->reject(fn ($slug) => $topics->has($slug))->values();

        $tagNames = $articles->pluck('tags')->flatten()->filter()->unique()->values();
        $tags = Tag::query()->active()->whereIn('name', $tagNames)->get()->keyBy('name');
        $missingTags = $tagNames->reject(fn ($name) => $tags->has($name))->values();

        $author = $this->resolveAuthor();

        $this->info('AI Orbit Editorial Coverage — Tools Batch 1');
        $this->table(['Check', 'Value'], [
            ['Dataset', $dataset],
            ['Articles', $articles->count()],
            ['Comparisons', $comparisons->count()],
            ['Unique tools referenced', $toolNames->count()],
            ['Missing tool names', $missingTools->count()],
            ['Referenced tools not published', $nonPublicTools->count()],
            ['Missing content topics', $missingTopics->count()],
            ['Missing optional tags', $missingTags->count()],
            ['Import mode', $this->option('publish') ? 'PUBLISH' : 'DRAFT / REVIEW'],
            ['Existing-slug behavior', $this->option('refresh') ? 'refresh' : 'skip'],
        ]);

        if ($missingTools->isNotEmpty()) {
            $this->error('Missing exact tool names: '.$missingTools->implode(', '));
        }
        if ($nonPublicTools->isNotEmpty()) {
            $this->warn('Referenced tools not currently published: '.$nonPublicTools->implode(', '));
        }
        if ($missingTopics->isNotEmpty()) {
            $this->error('Missing active Content Topics: '.$missingTopics->implode(', '));
            $this->line('Run `php artisan taxonomy:v2-sync` before importing this dataset.');
        }
        if ($missingTags->isNotEmpty()) {
            $this->warn('Optional tags not found and will be skipped: '.$missingTags->implode(', '));
        }
        if (! $author) {
            $this->error('No user exists to own imported articles. Pass --author=email@example.com or create an active user.');
        } else {
            $this->line('Article author: '.$author->name.' <'.$author->email.'>');
        }

        $comparisonValidation = $this->validateComparisonPairs($comparisons, $tools)
            ->merge($this->validateComparisonIntents($comparisons))
            ->values();
        if ($comparisonValidation->isNotEmpty()) {
            foreach ($comparisonValidation as $warning) {
                $this->error($warning);
            }
        }

        if ($missingTools->isNotEmpty() || $missingTopics->isNotEmpty() || ! $author || $comparisonValidation->isNotEmpty()) {
            return self::FAILURE;
        }

        $this->table(
            ['Article', 'Primary editorial cluster', 'Related tools'],
            $articles->map(fn ($row) => [
                Str::limit((string) ($row['title'] ?? ''), 58),
                (string) ($row['topic'] ?? ''),
                collect($row['tools'] ?? [])->implode(', '),
            ])->all()
        );

        $this->table(
            ['Comparison', 'Intent'],
            $comparisons->map(fn ($row) => [
                collect($row['items'] ?? [])->implode(' vs '),
                (string) ($row['intent'] ?? ''),
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No database records were changed.');
            return self::SUCCESS;
        }

        $publish = (bool) $this->option('publish');
        $refresh = (bool) $this->option('refresh');

        [$articleCreated, $articleUpdated, $articleSkipped] = $this->importArticles(
            $articles, $tools, $topics, $tags, $author, $publish, $refresh
        );

        [$comparisonCreated, $comparisonUpdated, $comparisonSkipped] = $this->importComparisons(
            $comparisons, $tools, $publish, $refresh
        );

        $this->newLine();
        $this->table(['Result', 'Created', 'Updated', 'Skipped'], [
            ['Articles', $articleCreated, $articleUpdated, $articleSkipped],
            ['Comparisons', $comparisonCreated, $comparisonUpdated, $comparisonSkipped],
        ]);

        if (! $publish) {
            $this->warn('Batch 1 was imported in DRAFT / REVIEW mode. Review the five guides and comparison pairs before publishing.');
            $this->line('After review, rerun: php artisan editorial:import-tools-b1 --publish --refresh');
        } else {
            $this->info('Batch 1 is published. Refresh the SEO intent map so newly indexable article/comparison URLs receive persisted intent owners:');
            $this->line('php artisan seo:audit-intent-map --sync');
            $this->line('php artisan seo:audit-health --details');
        }

        return self::SUCCESS;
    }

    private function validateComparisonPairs($comparisons, $tools)
    {
        return $comparisons->flatMap(function ($row, $index) use ($tools) {
            $items = collect($row['items'] ?? [])->filter()->values();
            $errors = collect();

            if ($items->count() !== 2) {
                $errors->push('Comparison row '.($index + 1).' must reference exactly two tools.');
                return $errors;
            }

            if ($items[0] === $items[1]) {
                $errors->push('Comparison row '.($index + 1).' references the same tool twice: '.$items[0]);
            }

            foreach ($items as $name) {
                if (! $tools->has($name)) {
                    $errors->push('Comparison row '.($index + 1).' cannot resolve tool: '.$name);
                }
            }

            return $errors;
        })->values();
    }

    private function validateComparisonIntents($comparisons)
    {
        return $comparisons->flatMap(function ($row, $index) {
            $intent = trim((string) ($row['intent'] ?? ''));
            $errors = collect();

            if ($intent === '') {
                $errors->push('Comparison row '.($index + 1).' is missing primary intent.');
                return $errors;
            }

            if (mb_strlen($intent) > 80) {
                $errors->push('Comparison row '.($index + 1).' primary intent exceeds comparisons.primary_intent limit (80 chars): '.mb_strlen($intent).' chars.');
            }

            return $errors;
        })->values();
    }

    private function importArticles($articles, $tools, $topics, $tags, User $author, bool $publish, bool $refresh): array
    {
        $created = $updated = $skipped = 0;
        $baseDate = CarbonImmutable::parse('2026-09-04 09:00:00', config('app.timezone'));

        foreach ($articles->values() as $offset => $row) {
            $article = Article::query()->where('slug', $row['slug'])->first();
            if ($article && ! $refresh) {
                $skipped++;
                continue;
            }

            $topic = $topics->get($row['topic']);
            $toolIds = collect($row['tools'] ?? [])->filter(fn ($name) => $tools->has($name))->map(fn ($name) => $tools[$name]->id)->values()->all();
            $tagIds = collect($row['tags'] ?? [])->filter(fn ($name) => $tags->has($name))->map(fn ($name) => $tags[$name]->id)->values()->all();
            $publishedAt = $baseDate->subMinutes($offset * 7);

            DB::transaction(function () use (&$article, $row, $author, $topic, $toolIds, $tagIds, $publish, $publishedAt, &$created, &$updated): void {
                $wasExisting = (bool) $article;
                $previousApproval = $article?->approval_status;
                $article ??= new Article();

                $article->fill([
                    'user_id' => $author->id,
                    'reviewer_id' => $publish ? $author->id : null,
                    'company_id' => null,
                    'category_id' => $topic->id,
                    'title' => $row['title'],
                    'slug' => $row['slug'],
                    'content' => $row['content'],
                    'summary' => $row['summary'],
                    'category' => $topic->name,
                    'tags' => collect($row['tags'] ?? [])->values()->all(),
                    'related_tools' => collect($row['tools'] ?? [])->values()->all(),
                    'related_models' => [],
                    'seo_title' => $row['seo_title'],
                    'meta_description' => $row['meta_description'],
                    'status' => $publish ? 'published' : 'draft',
                    'approval_status' => $publish ? 'approved' : 'draft',
                    'published_at' => $publish ? $publishedAt : null,
                    'submitted_for_review_at' => $publish ? $publishedAt : null,
                    'approved_at' => $publish ? $publishedAt : null,
                ]);
                $article->save();

                $article->tagTerms()->sync($tagIds);
                $article->relatedToolTerms()->sync($toolIds);
                $article->relatedModelTerms()->sync([]);

                ArticleWorkflowEvent::create([
                    'article_id' => $article->id,
                    'user_id' => $author->id,
                    'from_status' => $wasExisting ? $previousApproval : null,
                    'to_status' => $publish ? 'approved' : 'draft',
                    'action' => $wasExisting ? 'editorial_coverage_b1_refreshed' : 'editorial_coverage_b1_imported',
                    'comment' => $publish
                        ? 'Editorial Coverage Tools Batch 1 reviewed and published.'
                        : 'Editorial Coverage Tools Batch 1 imported as draft for editorial review.',
                ]);

                $wasExisting ? $updated++ : $created++;
            });
        }

        return [$created, $updated, $skipped];
    }

    private function importComparisons($comparisons, $tools, bool $publish, bool $refresh): array
    {
        $created = $updated = $skipped = 0;
        $allExisting = Comparison::query()->where('comparable_type', 'tool')->get();

        foreach ($comparisons as $row) {
            $names = collect($row['items'])->values();
            $ids = $names->map(fn ($name) => (int) $tools[$name]->id)->all();
            $sortedIds = collect($ids)->sort()->values()->all();

            $comparison = $allExisting->first(function (Comparison $candidate) use ($sortedIds) {
                $candidateIds = collect($candidate->item_ids ?? [])->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)->sort()->values()->all();
                return $candidateIds === $sortedIds;
            });

            $slug = Str::slug($names[0].'-vs-'.$names[1]);
            $comparison ??= Comparison::query()->where('slug', $slug)->first();

            if ($comparison && ! $refresh) {
                $skipped++;
                continue;
            }

            $wasExisting = (bool) $comparison;
            $comparison ??= new Comparison();
            $comparison->fill([
                'title' => $names[0].' vs '.$names[1],
                'slug' => $comparison->slug ?: $slug,
                'comparable_type' => 'tool',
                'item_ids' => $ids,
                'status' => $publish ? 'published' : 'draft',
                'comparison_version' => 'editorial-tools-b1-v1',
                'summary' => $row['summary'],
                'primary_intent' => $row['intent'],
                'last_verified_at' => now(),
                'auto_generated' => false,
                'seo_faq' => [
                    [
                        'question' => 'Which is better: '.$names[0].' or '.$names[1].'?',
                        'answer' => 'The better choice depends on the workflow, budget and capabilities you need. AI Orbit compares the current structured catalog data side by side rather than assigning a universal winner.',
                    ],
                    [
                        'question' => 'How should I use this comparison?',
                        'answer' => 'Start with the features and use cases that matter to your workflow, then review current pricing and product status from the linked product pages before deciding.',
                    ],
                ],
            ]);
            $comparison->save();

            $wasExisting ? $updated++ : $created++;
            $allExisting->push($comparison);
        }

        return [$created, $updated, $skipped];
    }

    private function resolveAuthor(): ?User
    {
        $email = trim((string) $this->option('author'));
        if ($email !== '') {
            return User::query()->where('email', $email)->first();
        }

        return User::query()->where('role', 'admin')->where('status', 'active')->orderBy('id')->first()
            ?? User::query()->where('status', 'active')->orderBy('id')->first()
            ?? User::query()->orderBy('id')->first();
    }
}
