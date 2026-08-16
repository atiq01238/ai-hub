<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleWorkflowEvent;
use Illuminate\Console\Command;

class PublishScheduledContent extends Command
{
    protected $signature = 'content:publish-scheduled';
    protected $description = 'Publish approved articles whose scheduled publish time has arrived.';

    public function handle(): int
    {
        $articles = Article::query()
            ->where('status', 'scheduled')
            ->where('approval_status', 'approved')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        foreach ($articles as $article) {
            $article->update(['status' => 'published']);
            ArticleWorkflowEvent::create([
                'article_id' => $article->id,
                'user_id' => null,
                'from_status' => 'approved',
                'to_status' => 'published',
                'action' => 'scheduled_publish',
                'comment' => 'Published automatically by the content scheduler.',
            ]);
        }

        $this->info("Published {$articles->count()} scheduled article(s).");

        return self::SUCCESS;
    }
}
