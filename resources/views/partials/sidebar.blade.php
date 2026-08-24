@php
    $discoveryModels = (int) ($discoveryNavCounts['models'] ?? 0);
    $discoveryTools = (int) ($discoveryNavCounts['tools'] ?? 0);
    $discoveryUpdates = (int) ($discoveryNavCounts['updates'] ?? 0);
    $discoveryBadgeParts = [];
    if ($discoveryModels > 0) $discoveryBadgeParts[] = 'M'.$discoveryModels;
    if ($discoveryTools > 0) $discoveryBadgeParts[] = 'T'.$discoveryTools;
    if ($discoveryUpdates > 0 && $discoveryModels === 0 && $discoveryTools === 0) $discoveryBadgeParts[] = 'U'.$discoveryUpdates;
    $discoveryBadge = $discoveryBadgeParts ? implode(' · ', $discoveryBadgeParts) : null;

    // Each item: [label, url path, icon, badge(optional), badgeType(optional)]
    $navGroups = [
        'Dashboard' => [
            ['Dashboard', '/admin', 'layout-dashboard', null, null, ['admin.dashboard']],
        ],
        'AI Intelligence' => [
            ['AI News Feed', '/admin/news', 'newspaper', null, null, ['admin.news.index']],
            ['AI Discovery', '/admin/discovery', 'radar', $discoveryBadge, 'warn', ['admin.discovery.*']],
            ['Breaking News', '/admin/news/breaking', 'zap'],
            ['Trending AI', '/admin/news/trending', 'trending-up'],
            ['AI Updates', '/admin/news/updates', 'refresh-cw'],
            ['Price Changes', '/admin/pricing/changes', 'tag'],
            ['Saved Intelligence', '/admin/news/saved', 'bookmark'],
            ['News Sources', '/admin/system/news-sources', 'server'],
            ['Automation Monitor', '/admin/system/automation-monitor', 'activity-square'],
        ],
        'AI Management' => [
            ['AI Tools', '/admin/tools', 'wrench', null, null, ['admin.tools.index','admin.tools.show','admin.tools.edit']],
            ['AI Models', '/admin/models', 'brain-circuit', null, null, ['admin.models.index','admin.models.show','admin.models.edit']],
            ['AI Companies', '/admin/companies', 'building-2', null, null, ['admin.companies.index','admin.companies.show','admin.companies.edit']],
            ['Data Import', '/admin/data-import', 'file-up', null, null, ['admin.data-import.*'], 'AI Companies', 'View'],
            ['Categories', '/admin/taxonomy/categories', 'shapes'],
            ['Sub Categories', '/admin/taxonomy/subcategories', 'list-tree'],
            ['Features', '/admin/taxonomy/features', 'sparkles'],
            ['Use Cases', '/admin/taxonomy/use-cases', 'target'],
            ['Tags', '/admin/taxonomy/tags', 'hash'],
            ['Content Topics', '/admin/taxonomy/content-topics', 'library-big'],
        ],
        'Comparison & Benchmarks' => [
            ['Comparisons', '/admin/comparisons', 'columns-3'],
            ['New Comparison', '/admin/comparisons/builder', 'square-stack'],
            ['Comparison Metrics', '/admin/comparisons/metrics', 'sliders-horizontal'],
            ['AI Test Lab', '/admin/testlab', 'flask-conical'],
            ['Benchmarks', '/admin/benchmarks', 'bar-chart-3'],
            ['Benchmark Results', '/admin/benchmarks/results', 'clipboard-check'],
            ['AI Test Results', '/admin/testlab/results', 'file-check-2'],
        ],
        'Pricing' => [
            ['Pricing Plans', '/admin/pricing', 'credit-card'],
            ['API Pricing', '/admin/pricing/api', 'server-cog'],
            ['Price History', '/admin/pricing/history', 'history'],
            ['Price Changes', '/admin/pricing/changes', 'arrow-up-down'],
        ],
        'Content' => [
            ['News Articles', '/admin/content/articles', 'file-text'],
            ['Article Drafts', '/admin/content/articles/drafts', 'file-edit'],
            ['Reviews', '/admin/content/reviews', 'message-square-heart', null, null, ['admin.content.reviews.*'], 'Reviews', 'View'],
            ['Guides', '/admin/content/guides', 'book-open'],
            ['Social Posts', '/admin/content/social', 'share-2'],
            ['Approval Workflow', '/admin/content/approval-workflow', 'workflow'],
            ['Media Library', '/admin/media', 'images'],
        ],
        'Users & Community' => [
            ['Users', '/admin/users', 'users', null, null, ['admin.users.index', 'admin.users.show'], 'Users', 'View'],
            ['Review Moderation', '/admin/community/reviews', 'message-circle', $communityNavCounts['reviews'] ?? null, 'warn', ['admin.community.reviews.*'], 'Reviews', 'View'],
            ['Community Submissions', '/admin/submissions', 'inbox', $communityNavCounts['submissions'] ?? null, 'warn', ['admin.submissions.*'], 'Submissions', 'View'],
            ['Contact Messages', '/admin/contact-messages', 'mail', $communityNavCounts['contacts'] ?? null, 'warn', ['admin.contact-messages.*'], 'Users', 'View'],
            ['Reports & Abuse', '/admin/community/reports', 'shield-alert', $communityNavCounts['reports'] ?? null, 'neg', ['admin.community.reports.*'], 'Reports', 'View'],
        ],
        'Analytics' => [
            ['Website Analytics', '/admin/analytics/website', 'activity'],
            ['Tool Analytics', '/admin/analytics/tools', 'pie-chart'],
            ['Search Analytics', '/admin/analytics/search', 'search'],
            ['Comparison Analytics', '/admin/analytics/comparisons', 'git-compare'],
            ['Content Analytics', '/admin/analytics/content', 'file-bar-chart'],
            ['Trending Searches', '/admin/analytics/trending', 'flame'],
            ['Engagement Intelligence', '/admin/analytics/engagement', 'users-round'],
        ],
        'System' => [
            ['System Overview', '/admin/system', 'command', null, null, ['admin.system.overview'], 'Security', 'View'],
            ['System Health', '/admin/system/health', 'heart-pulse', null, null, ['admin.system.health'], 'System Health', 'View'],
            ['Error Monitoring', '/admin/system/errors', 'octagon-alert', null, null, ['admin.system.errors.*'], 'Error Monitoring', 'View'],
            ['API Monitoring', '/admin/system/api-monitoring', 'plug-zap', null, null, ['admin.system.api-monitoring*'], 'API Monitoring', 'View'],
            ['Security Center', '/admin/system/security', 'lock', null, null, ['admin.system.security*'], 'Security', 'View'],
            ['Two-Factor Auth', '/admin/system/2fa', 'shield-check'],
            ['Roles & Permissions', '/admin/system/roles', 'shield-check', null, null, ['admin.system.roles*'], 'Roles & Permissions', 'View'],
            ['Activity Logs', '/admin/system/activity-logs', 'scroll-text', null, null, ['admin.system.activity-logs'], 'Security', 'View'],
            ['Backups', '/admin/system/backups', 'database-backup', null, null, ['admin.system.backups*'], 'Backups', 'View'],
            ['Notifications', '/admin/system/notifications', 'bell'],
            ['Email Deliveries', '/admin/system/email-deliveries', 'mail-check'],
            ['Notification Rules', '/admin/system/notification-rules', 'bell-ring'],
            ['Data Verification', '/admin/system/data-verification', 'file-check-2'],
            ['Source Reliability', '/admin/system/source-reliability', 'gauge-circle'],
            ['Feature Flags', '/admin/system/feature-flags', 'flag-triangle-right'],
            ['Integrations', '/admin/system/integrations', 'blocks'],
            ['SEO', '/admin/system/seo', 'search-check'],
            ['Settings', '/admin/system/settings', 'settings'],
        ],
    ];

    // Central route-to-permission map. Existing tuple-level permissions still win.
    $permissionByPath = [
        '/admin' => ['Dashboard','View'],
        '/admin/news' => ['AI News','View'], '/admin/discovery' => ['AI News','View'], '/admin/news/breaking' => ['AI News','View'], '/admin/news/trending' => ['AI News','View'], '/admin/news/updates' => ['AI News','View'], '/admin/news/saved' => ['AI News','View'],
        '/admin/tools' => ['AI Tools','View'], '/admin/models' => ['AI Models','View'], '/admin/companies' => ['AI Companies','View'],
        '/admin/taxonomy/categories' => ['Taxonomy','View'], '/admin/taxonomy/subcategories' => ['Taxonomy','View'], '/admin/taxonomy/features' => ['Taxonomy','View'], '/admin/taxonomy/use-cases' => ['Taxonomy','View'], '/admin/taxonomy/tags' => ['Taxonomy','View'], '/admin/taxonomy/content-topics' => ['Taxonomy','View'],
        '/admin/comparisons' => ['Comparisons','View'], '/admin/comparisons/builder' => ['Comparisons','Add'], '/admin/comparisons/metrics' => ['Comparisons','View'],
        '/admin/testlab' => ['AI Test Lab','View'], '/admin/testlab/results' => ['AI Test Lab','View'], '/admin/benchmarks' => ['Benchmarks','View'], '/admin/benchmarks/results' => ['Benchmarks','View'],
        '/admin/pricing' => ['Pricing','View'], '/admin/pricing/api' => ['Pricing','View'], '/admin/pricing/history' => ['Pricing','View'], '/admin/pricing/changes' => ['Pricing','View'],
        '/admin/content/articles' => ['Content','View'], '/admin/content/articles/drafts' => ['Content','View'], '/admin/content/reviews' => ['Reviews','View'], '/admin/content/guides' => ['Content','View'], '/admin/content/social' => ['Content','View'], '/admin/content/approval-workflow' => ['Content','View'], '/admin/media' => ['Content','View'],
        '/admin/users' => ['Users','View'], '/admin/community/reviews' => ['Reviews','View'], '/admin/submissions' => ['Submissions','View'], '/admin/contact-messages' => ['Users','View'], '/admin/community/reports' => ['Reports','View'],
        '/admin/analytics/website' => ['Analytics','View'], '/admin/analytics/tools' => ['Analytics','View'], '/admin/analytics/search' => ['Analytics','View'], '/admin/analytics/comparisons' => ['Analytics','View'], '/admin/analytics/content' => ['Analytics','View'], '/admin/analytics/trending' => ['Analytics','View'], '/admin/analytics/engagement' => ['Analytics','View'],
        '/admin/system' => ['Security','View'], '/admin/system/health' => ['System Health','View'], '/admin/system/errors' => ['Error Monitoring','View'], '/admin/system/api-monitoring' => ['API Monitoring','View'], '/admin/system/security' => ['Security','View'], '/admin/system/roles' => ['Roles & Permissions','View'], '/admin/system/activity-logs' => ['Security','View'], '/admin/system/backups' => ['Backups','View'],
        '/admin/system/notifications' => ['Notifications','View'], '/admin/system/email-deliveries' => ['Notifications','View'], '/admin/system/notification-rules' => ['Notifications','View'], '/admin/system/data-verification' => ['Data Verification','View'], '/admin/system/source-reliability' => ['Source Reliability','View'], '/admin/system/news-sources' => ['News Sources','View'], '/admin/system/automation-monitor' => ['Automation','View'], '/admin/system/feature-flags' => ['Feature Flags','View'], '/admin/system/integrations' => ['Integrations','View'], '/admin/system/seo' => ['SEO','View'], '/admin/system/settings' => ['Settings','View'],
    ];
    $current = request()->path();
@endphp

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand__mark sidebar-brand__mark--orbit">
            <img src="{{ asset(config('brand.assets.icon')) }}" alt="" aria-hidden="true">
        </div>
        <div class="sidebar-brand__text">
            <div class="sidebar-brand__title">AI Orbit</div>
            <div class="sidebar-brand__sub">AI Research &amp; Intelligence Platform</div>
        </div>
    </div>

    <nav class="sidebar-scroll">
        @foreach ($navGroups as $group => $items)
            @php
                $visibleItems = collect($items)->filter(function ($item) use ($permissionByPath) {
                    $url = $item[1];
                    $permissionModule = $item[6] ?? ($permissionByPath[$url][0] ?? null);
                    $permissionAction = $item[7] ?? ($permissionByPath[$url][1] ?? null);
                    return ! $permissionModule || auth()->user()?->canAccessModule($permissionModule, $permissionAction);
                });
            @endphp
            @continue($visibleItems->isEmpty())
            <div class="nav-group">
                <div class="nav-group__label">{{ $group }}</div>
                @foreach ($visibleItems as $item)
                    @php
                        [$label, $url, $icon] = $item;
                        $badge = $item[3] ?? null;
                        $badgeType = $item[4] ?? 'info';
                        $activeRoutes = $item[5] ?? [];
                        $isActive = $activeRoutes
                            ? request()->routeIs(...$activeRoutes)
                            : ($current === ltrim($url, '/') || str_starts_with($current, ltrim($url, '/').'/'));
                        $badgeClass = match ($badgeType) {
                            'warn' => 'is-warn',
                            'info' => 'is-info',
                            default => '',
                        };
                    @endphp
                    <a href="{{ url($url) }}" class="nav-item {{ $isActive ? 'is-active' : '' }}" title="{{ $label }}" @if($isActive) aria-current="page" @endif>
                        <i data-lucide="{{ $icon }}"></i>
                        <span class="nav-item__label">{{ $label }}</span>
                        @if($badge)
                            <span class="nav-badge {{ $badgeClass }}">{{ $badge }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>
</aside>
