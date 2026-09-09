<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\Company;
use App\Models\Comparison;
use App\Models\NewsItem;
use App\Models\Review;
use App\Models\Tool;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AuditTechnicalSeoCrawl extends Command
{
    protected $signature = 'seo:audit-technical-crawl {--details : Show sample invalid comparison rows and configured pagination routes}';

    protected $description = 'Audit AI Orbit canonical origin, robots/sitemaps, public pagination depth and crawl-trap protections.';

    public function handle(): int
    {
        $canonical = rtrim((string) config('seo.canonical_url'), '/');
        $host = (string) parse_url($canonical, PHP_URL_HOST);
        $scheme = (string) parse_url($canonical, PHP_URL_SCHEME);
        $robotsPath = public_path('robots.txt');
        $robots = is_file($robotsPath) ? (string) file_get_contents($robotsPath) : '';
        $htaccessPath = public_path('.htaccess');
        $htaccess = is_file($htaccessPath) ? (string) file_get_contents($htaccessPath) : '';
        $expectedSitemap = 'Sitemap: '.$canonical.'/sitemap.xml';

        $requiredSitemapRoutes = [
            'sitemap.index', 'sitemap.companies', 'sitemap.tools', 'sitemap.models',
            'sitemap.news', 'sitemap.articles', 'sitemap.reviews', 'sitemap.pricing',
            'sitemap.comparisons', 'sitemap.benchmarks', 'sitemap.taxonomy', 'sitemap.pages',
        ];

        $this->info('AI Orbit Phase 7 technical SEO & crawl audit');
        $this->table(['Technical check', 'Result'], [
            ['Canonical origin', $canonical ?: 'MISSING'],
            ['HTTPS canonical', $scheme === 'https' ? 'PASS' : 'ATTENTION'],
            ['Non-www canonical host', $host !== '' && ! str_starts_with(strtolower($host), 'www.') ? 'PASS' : 'ATTENTION'],
            ['robots.txt present', $robots !== '' ? 'PASS' : 'ATTENTION'],
            ['robots sitemap matches canonical', str_contains($robots, $expectedSitemap) ? 'PASS' : 'ATTENTION'],
            ['Health endpoint blocked in robots', preg_match('/^Disallow:\s*\/up\s*$/mi', $robots) ? 'PASS' : 'ATTENTION'],
            ['Direct /index.php redirect configured', str_contains($htaccess, 'RewriteRule ^index\.php$ / [R=301,L]') ? 'PASS' : 'ATTENTION'],
            ['Required sitemap routes available', collect($requiredSitemapRoutes)->every(fn ($name) => Route::has($name)) ? 'PASS' : 'ATTENTION'],
            ['Canonical pagination routes configured', count((array) config('seo.indexable_pagination_routes', [])) > 0 ? 'PASS' : 'ATTENTION'],
        ]);

        $validComparisons = $this->validComparisons();
        $publishedComparisons = Comparison::query()->where('status', 'published')->count();

        $rows = [
            ['AI Tools', Tool::query()->where('status', 'published')->count(), 12],
            ['AI Models', AiModel::query()->whereIn('status', ['active', 'preview'])->count(), 12],
            ['AI News', NewsItem::query()->publiclyVisible()->count(), 12],
            ['Articles', Article::query()->where('status', 'published')->where('approval_status', 'approved')->count(), 12],
            ['Companies', Company::query()->seoIndexable()->count(), 12],
            ['Reviews', Review::query()->publicContent()->count(), 12],
            ['Comparisons', $validComparisons->count(), 9],
        ];

        $this->newLine();
        $this->table(
            ['Canonical paginated surface', 'Eligible rows', 'Per page', 'Last page'],
            collect($rows)->map(fn ($row) => [$row[0], $row[1], $row[2], max(1, (int) ceil($row[1] / $row[2]))])->all()
        );

        $invalidComparisons = max(0, $publishedComparisons - $validComparisons->count());
        $this->newLine();
        $this->table(['Crawl cleanup signal', 'Count'], [
            ['Published comparisons resolving fewer than 2 public items', $invalidComparisons],
            ['Canonical public pagination routes', count((array) config('seo.indexable_pagination_routes', []))],
        ]);

        if ($this->option('details')) {
            $this->newLine();
            $this->comment('Indexable pagination route names');
            foreach ((array) config('seo.indexable_pagination_routes', []) as $routeName) {
                $this->line(' - '.$routeName);
            }

            $invalid = Comparison::query()
                ->where('status', 'published')
                ->get()
                ->reject(fn (Comparison $comparison) => $validComparisons->contains('id', $comparison->id))
                ->take(25);

            if ($invalid->isNotEmpty()) {
                $this->newLine();
                $this->comment('Invalid comparison samples withheld from crawlable directory pagination');
                $this->table(
                    ['ID', 'Title', 'Slug'],
                    $invalid->map(fn (Comparison $comparison) => [$comparison->id, $comparison->title, $comparison->slug])->all()
                );
            }
        }

        $this->newLine();
        $this->info('Phase 7 audit complete. This command does not modify database records.');

        return self::SUCCESS;
    }

    private function validComparisons(): Collection
    {
        return Comparison::query()
            ->where('status', 'published')
            ->get()
            ->filter(function (Comparison $comparison) {
                try {
                    return $comparison->publicItems()->count() >= 2;
                } catch (\Throwable $e) {
                    report($e);
                    return false;
                }
            })
            ->values();
    }
}
