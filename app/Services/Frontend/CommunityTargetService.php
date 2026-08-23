<?php

namespace App\Services\Frontend;

use App\Models\AiModel;
use App\Models\AiTest;
use App\Models\Article;
use App\Models\Benchmark;
use App\Models\Comparison;
use App\Models\NewsItem;
use App\Models\Review;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CommunityTargetService
{
    public const COMMENT_TYPES = ['news', 'article', 'comparison', 'benchmark', 'test'];
    public const REVIEW_TYPES = ['tool', 'model'];

    public function resolve(string $type, int $id): Model
    {
        return match ($type) {
            'tool' => Tool::query()->where('status', 'published')->findOrFail($id),
            'model' => AiModel::query()->whereIn('status', ['active', 'preview'])->findOrFail($id),
            'news' => NewsItem::query()
                ->where('status', 'published')
                ->whereNull('duplicate_of_id')
                ->findOrFail($id),
            'article' => Article::query()
                ->where('status', 'published')
                ->where('approval_status', 'approved')
                ->findOrFail($id),
            'comparison' => Comparison::query()->where('status', 'published')->findOrFail($id),
            'benchmark' => Benchmark::query()->where('is_active', true)->findOrFail($id),
            'test' => AiTest::query()->published()->findOrFail($id),
            default => abort(422),
        };
    }

    public function resolvePath(Request $request, string $path): ?array
    {
        $path = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?: '', '/');

        if (preg_match('#^/ai-tools/([^/]+)$#', $path, $m)) {
            $target = Tool::where('slug', rawurldecode($m[1]))->where('status', 'published')->first();
            return $target ? $this->context('tool', $target) : null;
        }

        if (preg_match('#^/ai-models/([^/]+)$#', $path, $m)) {
            $target = AiModel::where('slug', rawurldecode($m[1]))->whereIn('status', ['active', 'preview'])->first();
            return $target ? $this->context('model', $target) : null;
        }

        if (preg_match('#^/ai-news/([^/]+)$#', $path, $m)) {
            $target = NewsItem::where('slug', rawurldecode($m[1]))
                ->where('status', 'published')->whereNull('duplicate_of_id')->first();
            return $target ? $this->context('news', $target) : null;
        }

        if (preg_match('#^/articles/([^/]+)$#', $path, $m)) {
            $target = Article::where('slug', rawurldecode($m[1]))
                ->where('status', 'published')->where('approval_status', 'approved')->first();
            return $target ? $this->context('article', $target) : null;
        }

        if (preg_match('#^/compare/([^/]+)$#', $path, $m)) {
            if (in_array($m[1], ['builder', 'preview'], true)) {
                return null;
            }

            $target = Comparison::where('slug', rawurldecode($m[1]))->where('status', 'published')->first();
            return $target ? $this->context('comparison', $target) : null;
        }

        if (preg_match('#^/test-lab/([^/]+)$#', $path, $m)) {
            if ($m[1] === 'leaderboard') return null;
            $value = rawurldecode($m[1]);
            $target = AiTest::query()->published()->where(function ($q) use ($value) {
                $q->where('slug', $value);
                if (ctype_digit($value)) $q->orWhere('id', (int) $value);
            })->first();
            return $target ? $this->context('test', $target) : null;
        }

        if (preg_match('#^/benchmarks/([^/]+)/discussion$#', $path, $m)) {
            $target = Benchmark::where('slug', rawurldecode($m[1]))->where('is_active', true)->first();
            return $target ? $this->context('benchmark', $target) : null;
        }

        return null;
    }

    public function label(Model $target): string
    {
        return (string) (
            $target->name
            ?? $target->headline
            ?? $target->title
            ?? ('Item #' . $target->getKey())
        );
    }

    public function reviewTarget(string $type, int $id): Tool|AiModel
    {
        abort_unless(in_array($type, self::REVIEW_TYPES, true), 422);

        /** @var Tool|AiModel $target */
        $target = $this->resolve($type, $id);

        return $target;
    }

    private function context(string $type, Model $target): array
    {
        return [
            'type' => $type,
            'id' => (int) $target->getKey(),
            'name' => $this->label($target),
            'mode' => in_array($type, self::REVIEW_TYPES, true) ? 'review' : 'comment',
        ];
    }
}
