<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\ArticleWorkflowEvent;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Tool;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class ImportCuratedArticles extends Command
{
    protected $signature = 'articles:import-curated
        {--dataset=v1-2026-08-22 : Curated article dataset version}
        {--dry-run : Validate and preview without saving}
        {--publish : Import as approved and published instead of draft}
        {--refresh : Update existing articles with matching slugs}
        {--author= : Author email; defaults to the first active admin/user}';

    protected $description = 'Import AI Hub curated evergreen articles with Taxonomy v2 topics, tags and related catalog entities.';

    public function handle(): int
    {
        $dataset = (string) $this->option('dataset');
        $path = storage_path("app/import-templates/curated-articles-{$dataset}.json");

        if (! File::exists($path)) {
            $this->error("Dataset not found: {$path}");
            return self::FAILURE;
        }

        try {
            $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $this->error('Invalid article dataset JSON: '.$e->getMessage());
            return self::FAILURE;
        }

        $rows = collect($payload['articles'] ?? []);
        if ($rows->isEmpty()) {
            $this->error('The selected article dataset is empty.');
            return self::FAILURE;
        }

        $author = $this->resolveAuthor();
        if (! $author) {
            $this->error('No user account exists to assign as the article author. Create an admin/user first or pass --author=email@example.com.');
            return self::FAILURE;
        }

        $topicSlugs = $rows->pluck('topic')->filter()->unique()->values();
        $tagNames = $rows->pluck('tags')->flatten()->filter()->unique()->values();
        $toolNames = $rows->pluck('tools')->flatten()->filter()->unique()->values();
        $modelNames = $rows->pluck('models')->flatten()->filter()->unique()->values();

        $topics = Category::query()->content()->active()->whereIn('slug', $topicSlugs)->get()->keyBy('slug');
        $tags = Tag::query()->active()->whereIn('name', $tagNames)->get()->keyBy('name');
        $tools = Tool::query()->whereIn('name', $toolNames)->get()->keyBy('name');
        $models = AiModel::query()->whereIn('name', $modelNames)->get()->keyBy('name');

        $missingTopics = $topicSlugs->reject(fn ($slug) => $topics->has($slug))->values();
        if ($missingTopics->isNotEmpty()) {
            $this->error('Missing active Content Topics: '.$missingTopics->implode(', '));
            $this->line('Run `php artisan taxonomy:v2-sync` before importing this dataset.');
            return self::FAILURE;
        }

        $missingTags = $tagNames->reject(fn ($name) => $tags->has($name))->values();
        $missingTools = $toolNames->reject(fn ($name) => $tools->has($name))->values();
        $missingModels = $modelNames->reject(fn ($name) => $models->has($name))->values();

        if ($missingTags->isNotEmpty()) {
            $this->warn('Missing tags will be skipped: '.$missingTags->implode(', '));
        }
        if ($missingTools->isNotEmpty()) {
            $this->warn('Missing related tools will be skipped: '.$missingTools->implode(', '));
        }
        if ($missingModels->isNotEmpty()) {
            $this->warn('Missing related models will be skipped: '.$missingModels->implode(', '));
        }

        $existingSlugs = Article::query()->whereIn('slug', $rows->pluck('slug'))->pluck('slug')->all();
        $existing = count($existingSlugs);
        $new = $rows->count() - $existing;
        $refresh = (bool) $this->option('refresh');
        $publish = (bool) $this->option('publish');

        $this->info('Curated Articles '.$dataset.': '.$rows->count().' articles.');
        $this->table(['Area', 'Count'], [
            ['Articles in dataset', $rows->count()],
            ['New slugs', $new],
            ['Existing slugs', $existing],
            ['Content Topics used', $topicSlugs->count()],
            ['Tags referenced', $tagNames->count()],
            ['Related tools referenced', $toolNames->count()],
            ['Related models referenced', $modelNames->count()],
        ]);
        $this->line('Author: '.$author->name.' <'.$author->email.'>');
        $this->line('Import mode: '.($publish ? 'APPROVED + PUBLISHED' : 'DRAFT').' / '.($refresh ? 'refresh existing' : 'skip existing'));

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No database changes were saved.');
            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $baseDate = CarbonImmutable::parse('2026-08-22 09:00:00', config('app.timezone'));

        foreach ($rows as $row) {
            $article = Article::query()->where('slug', $row['slug'])->first();
            if ($article && ! $refresh) {
                $skipped++;
                continue;
            }

            $topic = $topics[$row['topic']];
            $articleTagIds = collect($row['tags'] ?? [])->filter(fn ($name) => $tags->has($name))->map(fn ($name) => $tags[$name]->id)->values()->all();
            $articleToolIds = collect($row['tools'] ?? [])->filter(fn ($name) => $tools->has($name))->map(fn ($name) => $tools[$name]->id)->values()->all();
            $articleModelIds = collect($row['models'] ?? [])->filter(fn ($name) => $models->has($name))->map(fn ($name) => $models[$name]->id)->values()->all();
            $publishedAt = $baseDate->subDays((int) ($row['published_days_ago'] ?? 0));

            DB::transaction(function () use (
                &$article, $row, $author, $topic, $articleTagIds, $articleToolIds, $articleModelIds,
                $publish, $publishedAt, &$created, &$updated
            ): void {
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
                    'featured_image_path' => $row['featured_image_path'] ?? $article->featured_image_path,
                    'content' => $row['content'],
                    'summary' => $row['summary'],
                    'category' => $topic->name,
                    'tags' => collect($row['tags'] ?? [])->values()->all(),
                    'related_tools' => collect($row['tools'] ?? [])->values()->all(),
                    'related_models' => collect($row['models'] ?? [])->values()->all(),
                    'seo_title' => $row['seo_title'],
                    'meta_description' => $row['meta_description'],
                    'status' => $publish ? 'published' : 'draft',
                    'approval_status' => $publish ? 'approved' : 'draft',
                    'published_at' => $publish ? $publishedAt : null,
                    'submitted_for_review_at' => $publish ? $publishedAt : null,
                    'approved_at' => $publish ? $publishedAt : null,
                ]);
                $article->save();

                $article->tagTerms()->sync($articleTagIds);
                $article->relatedToolTerms()->sync($articleToolIds);
                $article->relatedModelTerms()->sync($articleModelIds);

                ArticleWorkflowEvent::create([
                    'article_id' => $article->id,
                    'user_id' => $author->id,
                    'from_status' => $wasExisting ? $previousApproval : null,
                    'to_status' => $publish ? 'approved' : 'draft',
                    'action' => $wasExisting ? 'curated_import_refreshed' : 'curated_imported',
                    'comment' => $publish
                        ? 'Imported from curated AI Hub dataset and published by explicit command option.'
                        : 'Imported from curated AI Hub dataset as a draft for editorial review.',
                ]);

                $wasExisting ? $updated++ : $created++;
            });
        }

        $this->info("Import complete: {$created} created, {$updated} updated, {$skipped} skipped.");
        if (! $publish) {
            $this->line('Articles are drafts. Review them in Admin → Content → Articles, or rerun with --publish --refresh after review.');
        }

        return self::SUCCESS;
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
