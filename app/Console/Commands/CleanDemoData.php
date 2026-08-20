<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\AiModel;
use App\Models\Company;
use App\Models\Tool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CleanDemoData extends Command
{
    protected $signature = 'data:clean-demo
        {--include-news : Also remove all existing news items and their derived discovery/social/bookmark data}
        {--include-community : Also remove submissions, reports, all community comments/reactions and saved/user interaction history}
        {--force : Skip confirmation prompts}';

    protected $description = 'Safely remove demo/catalog content while preserving users, roles, permissions, settings and source configuration.';

    /** @var array<string, string> */
    private array $preserved = [
        'users' => 'Admin/user accounts',
        'roles' => 'Roles',
        'role_permissions' => 'Role permissions',
        'settings' => 'System settings',
        'feature_flags' => 'Feature flags',
        'notification_rules' => 'Notification rules',
        'news_sources' => 'RSS/news source configuration',
        'discovery_sources' => 'AI Discovery source configuration',
        'login_attempts' => 'Login/security history',
        'contact_messages' => 'Contact inbox',
    ];

    public function handle(): int
    {
        $includeNews = (bool) $this->option('include-news');
        $includeCommunity = (bool) $this->option('include-community');
        $force = (bool) $this->option('force');

        $this->newLine();
        $this->components->info('AI Hub demo-data cleanup');
        $this->line('This command removes demo/catalog records, not Laravel/system configuration.');
        $this->newLine();

        $plan = $this->buildPlan($includeNews, $includeCommunity);
        $this->showPlan($plan, $includeNews, $includeCommunity);

        if (!$force) {
            if (!$includeNews && $this->confirm('Also remove ALL existing News items? This can include real RSS-fetched news.', false)) {
                $includeNews = true;
                $plan = $this->buildPlan($includeNews, $includeCommunity);
                $this->newLine();
                $this->warn('News cleanup enabled.');
                $this->showPlan($plan, $includeNews, $includeCommunity);
            }

            if (!$includeCommunity && $this->confirm('Also remove submissions/reports and ALL user community/history data?', false)) {
                $includeCommunity = true;
                $plan = $this->buildPlan($includeNews, $includeCommunity);
                $this->newLine();
                $this->warn('Community/history cleanup enabled.');
                $this->showPlan($plan, $includeNews, $includeCommunity);
            }

            if (!$this->confirm('Proceed with this cleanup?', false)) {
                $this->components->warn('Cleanup cancelled. Nothing was deleted.');
                return self::SUCCESS;
            }
        }

        try {
            $deleted = $this->runCleanup($includeNews, $includeCommunity);
        } catch (Throwable $e) {
            $this->components->error('Cleanup failed: ' . $e->getMessage());
            $this->line('No additional cleanup steps were attempted after the failure.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Cleanup completed successfully.');
        $this->table(['Area', 'Rows removed'], collect($deleted)->map(fn ($count, $area) => [$area, $count])->values()->all());
        $this->newLine();
        $this->line('Preserved: users/admins, roles/permissions, settings, feature flags, RSS/news sources and Discovery source configuration.');
        $this->line('You can now start entering verified real Companies → Models → Tools data.');

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    private function buildPlan(bool $includeNews, bool $includeCommunity): array
    {
        $tables = [
            'articles',
            'benchmarks',
            'benchmark_results',
            'comparisons',
            'pricing_plans',
            'pricing_history',
            'pricing_sources',
            'detected_price_changes',
            'reviews',
            'ai_tests',
            'ai_test_results',
            'tools',
            'ai_models',
            'companies',
        ];

        if ($includeNews) {
            array_push($tables, 'news_items', 'news_bookmarks', 'social_posts', 'ai_discoveries');
        }

        if ($includeCommunity) {
            array_push(
                $tables,
                'submissions',
                'reports',
                'community_comments',
                'community_reactions',
                'saved_items',
                'user_comparisons',
                'user_interactions',
                'saved_searches',
                'search_events'
            );
        }

        $plan = [];
        foreach (array_unique($tables) as $table) {
            if (Schema::hasTable($table)) {
                $plan[$table] = (int) DB::table($table)->count();
            }
        }

        return $plan;
    }

    /** @param array<string, int> $plan */
    private function showPlan(array $plan, bool $includeNews, bool $includeCommunity): void
    {
        $this->table(
            ['Data area', 'Current rows'],
            collect($plan)->map(fn ($count, $table) => [$table, $count])->values()->all()
        );

        $this->newLine();
        $this->line('<fg=green>Always preserved:</> ' . implode(', ', array_keys($this->preserved)));
        $this->line('News items: ' . ($includeNews ? '<fg=red>DELETE ALL</>' : '<fg=green>PRESERVE</>'));
        $this->line('Community/history: ' . ($includeCommunity ? '<fg=red>DELETE ALL</>' : '<fg=yellow>PRESERVE, except references to deleted catalog content are cleaned</>'));
    }

    /** @return array<string, int> */
    private function runCleanup(bool $includeNews, bool $includeCommunity): array
    {
        $deleted = [];

        DB::transaction(function () use ($includeNews, $includeCommunity, &$deleted): void {
            // Remove polymorphic/user references that would otherwise point at deleted demo content.
            $deleted['Catalog references'] = $this->deleteCatalogReferences($includeNews, $includeCommunity);

            // Children/pivots first. Most have FK cascades as well; explicit deletes keep the operation predictable.
            $deleted['Article links/workflow'] = $this->deleteTables([
                'article_tool', 'ai_model_article', 'article_tag', 'article_workflow_events',
            ]);
            $deleted['Articles'] = $this->deleteTables(['articles']);

            $deleted['Benchmark data'] = $this->deleteTables(['benchmark_results', 'benchmarks']);
            $deleted['Pricing data'] = $this->deleteTables([
                'detected_price_changes', 'pricing_history', 'pricing_sources', 'pricing_plans',
            ]);
            $deleted['Reviews'] = $this->deleteTables(['reviews']);
            $deleted['Test Lab data'] = $this->deleteTables(['ai_test_results', 'ai_tests']);
            $deleted['Comparisons'] = $this->deleteTables(['comparisons']);

            // Tool taxonomy itself is intentionally preserved; only pivots connected to demo tools are removed.
            $deleted['Tool taxonomy links'] = $this->deleteTables(['feature_tool', 'tag_tool']);

            // Catalog parents last.
            $deleted['AI models'] = $this->deleteTables(['ai_models']);
            $deleted['AI tools'] = $this->deleteTables(['tools']);
            $deleted['Companies'] = $this->deleteTables(['companies']);

            if ($includeNews) {
                $deleted['News derived data'] = $this->deleteTables(['news_bookmarks', 'social_posts', 'ai_discoveries']);
                $deleted['News items'] = $this->deleteTables(['news_items']);
            }

            if ($includeCommunity) {
                $deleted['Community & submissions'] = $this->deleteTables([
                    'community_reactions', 'community_comments', 'reports', 'submissions',
                    'saved_items', 'user_comparisons', 'user_interactions', 'saved_searches', 'search_events',
                ]);
            }
        }, 3);

        return array_filter($deleted, fn ($count) => $count > 0);
    }

    private function deleteCatalogReferences(bool $includeNews, bool $includeCommunity): int
    {
        if ($includeCommunity) {
            return 0; // All of these tables are deleted later in full.
        }

        $deleted = 0;

        if (Schema::hasTable('saved_items')) {
            $types = [Tool::class, AiModel::class, Company::class, Article::class];
            $deleted += DB::table('saved_items')->whereIn('saveable_type', $types)->delete();
        }

        if (Schema::hasTable('user_interactions')) {
            $deleted += DB::table('user_interactions')
                ->whereIn('target_type', ['tool', 'model', 'company', 'review', 'test'])
                ->delete();
        }

        if (Schema::hasTable('user_comparisons')) {
            $deleted += DB::table('user_comparisons')->delete();
        }

        if (Schema::hasTable('community_comments')) {
            $types = ['article', 'comparison', 'benchmark', 'test'];
            if ($includeNews) {
                $types[] = 'news';
            }

            $commentIds = DB::table('community_comments')
                ->whereIn('commentable_type', $types)
                ->pluck('id');

            if ($commentIds->isNotEmpty()) {
                if (Schema::hasTable('community_reactions')) {
                    $deleted += DB::table('community_reactions')
                        ->where('reactable_type', 'comment')
                        ->whereIn('reactable_id', $commentIds)
                        ->delete();
                }

                $deleted += DB::table('community_comments')->whereIn('id', $commentIds)->delete();
            }
        }

        if ($includeNews && Schema::hasTable('search_events')) {
            $deleted += DB::table('search_events')->where('clicked_type', 'news')->delete();
        }

        return $deleted;
    }

    /** @param list<string> $tables */
    private function deleteTables(array $tables): int
    {
        $deleted = 0;

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $deleted += DB::table($table)->delete();
        }

        return $deleted;
    }
}
