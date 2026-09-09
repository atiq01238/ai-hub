<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

class AuditEditorialTrust extends Command
{
    protected $signature = 'trust:audit-editorial {--details : Show route and article trust details}';

    protected $description = 'Audit AI Orbit Phase 6 editorial transparency and trust surfaces without modifying data.';

    public function handle(): int
    {
        $routes = [
            'about',
            'methodology',
            'editorial-guidelines',
            'sourcing-verification',
            'corrections-policy',
            'disclosures',
            'contact',
            'privacy',
        ];

        $routeRows = collect($routes)->map(function (string $name) {
            return [
                $name,
                app('router')->has($name) ? 'YES' : 'NO',
                app('router')->has($name) ? route($name) : '—',
            ];
        });

        $published = Article::query()->where('status', 'published')->get();
        $approved = $published->where('approval_status', 'approved');
        $reviewed = $published->filter(fn (Article $article) => filled($article->reviewer_id));
        $approvalDated = $published->filter(fn (Article $article) => filled($article->approved_at));
        $authored = $published->filter(fn (Article $article) => filled($article->user_id));

        $this->info('AI Orbit Phase 6 editorial trust audit');
        $this->table(['Metric', 'Count'], [
            ['Published articles', $published->count()],
            ['Editorially approved', $approved->count()],
            ['Named author attached', $authored->count()],
            ['Named reviewer attached', $reviewed->count()],
            ['Approval/review date recorded', $approvalDated->count()],
            ['Transparency routes available', $routeRows->where(1, 'YES')->count().'/'.$routeRows->count()],
        ]);

        if ($this->option('details')) {
            $this->newLine();
            $this->table(['Trust route', 'Available', 'URL'], $routeRows->all());

            $gaps = $published->filter(fn (Article $article) =>
                $article->approval_status !== 'approved'
                || blank($article->user_id)
                || blank($article->reviewer_id)
                || blank($article->approved_at)
            )->take(30)->map(fn (Article $article) => [
                $article->id,
                str($article->title)->limit(54),
                $article->approval_status ?: '—',
                $article->user_id ? 'YES' : 'NO',
                $article->reviewer_id ? 'YES' : 'NO',
                $article->approved_at?->format('Y-m-d') ?: '—',
            ])->values();

            if ($gaps->isNotEmpty()) {
                $this->newLine();
                $this->warn('Published article trust metadata gaps (sample):');
                $this->table(['ID', 'Title', 'Approval', 'Author', 'Reviewer', 'Review date'], $gaps->all());
            }
        }

        $this->newLine();
        $this->line('Audit complete. No database records were modified.');

        return self::SUCCESS;
    }
}
