<?php

namespace App\Services\Analytics;

use App\Models\AnalyticsPageView;
use App\Models\AnalyticsSession;
use App\Models\AnalyticsVisitor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitorTrackingService
{
    public function record(Request $request, string $visitorToken): void
    {
        $now = now();
        $visitorKey = hash('sha256', $visitorToken);
        $userId = $request->user()?->getAuthIdentifier();
        $path = '/' . ltrim($request->path(), '/');
        if ($path === '//') {
            $path = '/';
        }

        [$referrerDomain, $utm] = $this->acquisition($request);

        $visitor = AnalyticsVisitor::query()->firstOrCreate(
            ['visitor_key' => $visitorKey],
            [
                'user_id' => $userId,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'first_landing_path' => $path,
                'first_referrer_domain' => $referrerDomain,
                'first_utm_source' => $utm['source'],
                'first_utm_medium' => $utm['medium'],
                'first_utm_campaign' => $utm['campaign'],
            ]
        );

        $visitor->forceFill(array_filter([
            'user_id' => $userId,
            'last_seen_at' => $now,
        ], fn ($value) => $value !== null))->save();

        $rawSessionId = $request->hasSession() ? (string) $request->session()->getId() : '';
        $sessionSeed = $rawSessionId !== '' ? $rawSessionId : $visitorToken . '|' . $now->format('Y-m-d-H');
        $sessionKey = hash('sha256', $sessionSeed . '|' . $visitorKey);

        $session = AnalyticsSession::query()->firstOrCreate(
            ['session_key' => $sessionKey],
            [
                'visitor_id' => $visitor->id,
                'user_id' => $userId,
                'started_at' => $now,
                'last_seen_at' => $now,
                'landing_path' => $path,
                'referrer_domain' => $referrerDomain,
                'utm_source' => $utm['source'],
                'utm_medium' => $utm['medium'],
                'utm_campaign' => $utm['campaign'],
                'device_type' => $this->deviceType((string) $request->userAgent()),
                'browser' => $this->browser((string) $request->userAgent()),
                'operating_system' => $this->operatingSystem((string) $request->userAgent()),
                'country_code' => $this->countryCode($request),
                'page_views' => 0,
            ]
        );

        $isEntry = $session->wasRecentlyCreated || (int) $session->page_views === 0;
        $session->forceFill(array_filter([
            'user_id' => $userId,
            'last_seen_at' => $now,
        ], fn ($value) => $value !== null))->save();
        $session->increment('page_views');

        [$entityType, $entityId] = $this->entity($request);

        AnalyticsPageView::query()->create([
            'visitor_id' => $visitor->id,
            'analytics_session_id' => $session->id,
            'user_id' => $userId,
            'route_name' => Str::limit((string) ($request->route()?->getName() ?? ''), 140, ''),
            'path' => Str::limit($path, 1024, ''),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'is_entry' => $isEntry,
            'viewed_at' => $now,
        ]);
    }

    public function isBot(?string $userAgent): bool
    {
        $ua = strtolower((string) $userAgent);
        if ($ua === '') {
            return true;
        }

        return (bool) preg_match('/bot|crawler|spider|slurp|bingpreview|facebookexternalhit|headless|lighthouse|gtmetrix|pingdom|uptimerobot|monitoring|curl|wget|python-requests|postmanruntime|go-http-client|httpclient|scrapy|semrush|ahrefs|mj12bot|bytespider|yandexbot/i', $ua);
    }

    private function acquisition(Request $request): array
    {
        $referrer = trim((string) $request->headers->get('referer', ''));
        $referrerDomain = null;

        if ($referrer !== '') {
            $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
            $currentHost = strtolower($request->getHost());
            if ($host !== '' && $host !== $currentHost && $host !== 'www.' . $currentHost && 'www.' . $host !== $currentHost) {
                $referrerDomain = preg_replace('/^www\./i', '', $host);
            } elseif ($host !== '') {
                $referrerDomain = 'Internal';
            }
        }

        return [$referrerDomain, [
            'source' => $this->cleanCampaignValue($request->query('utm_source'), 100),
            'medium' => $this->cleanCampaignValue($request->query('utm_medium'), 100),
            'campaign' => $this->cleanCampaignValue($request->query('utm_campaign'), 150),
        ]];
    }

    private function cleanCampaignValue(mixed $value, int $limit): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : Str::limit($value, $limit, '');
    }

    private function entity(Request $request): array
    {
        $routeName = (string) ($request->route()?->getName() ?? '');
        $map = [
            'tools.show' => ['tool', 'tool'],
            'models.show' => ['model', 'model'],
            'companies.show' => ['company', 'company'],
            'news.show' => ['news', 'news'],
            'articles.show' => ['article', 'article'],
            'comparisons.show' => ['comparison', 'comparison'],
            'pricing.show' => ['tool', 'tool'],
        ];

        if (! isset($map[$routeName])) {
            return [null, null];
        }

        [$parameter, $type] = $map[$routeName];
        $value = $request->route($parameter);
        $id = $value instanceof Model ? $value->getKey() : (is_numeric($value) ? (int) $value : null);

        return [$id ? $type : null, $id ?: null];
    }

    private function deviceType(string $ua): string
    {
        if (preg_match('/ipad|tablet|kindle|silk|(android(?!.*mobile))/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/mobile|iphone|ipod|android|blackberry|opera mini|iemobile/i', $ua)) {
            return 'mobile';
        }
        return 'desktop';
    }

    private function browser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'SamsungBrowser/') => 'Samsung Internet',
            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS/') => 'Chrome',
            str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS/') => 'Firefox',
            str_contains($ua, 'Safari/') => 'Safari',
            str_contains($ua, 'MSIE') || str_contains($ua, 'Trident/') => 'Internet Explorer',
            default => 'Other',
        };
    }

    private function operatingSystem(string $ua): string
    {
        return match (true) {
            preg_match('/windows nt/i', $ua) === 1 => 'Windows',
            preg_match('/iphone|ipad|ipod/i', $ua) === 1 => 'iOS',
            preg_match('/android/i', $ua) === 1 => 'Android',
            preg_match('/macintosh|mac os x/i', $ua) === 1 => 'macOS',
            preg_match('/cros/i', $ua) === 1 => 'Chrome OS',
            preg_match('/linux/i', $ua) === 1 => 'Linux',
            default => 'Other',
        };
    }

    private function countryCode(Request $request): ?string
    {
        foreach (['CF-IPCountry', 'CloudFront-Viewer-Country', 'X-Country-Code'] as $header) {
            $value = strtoupper(trim((string) $request->headers->get($header, '')));
            if (preg_match('/^[A-Z]{2}$/', $value)) {
                return $value;
            }
        }

        return null;
    }
}
