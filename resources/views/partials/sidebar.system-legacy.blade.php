@php
    // Each item: [label, url path, icon, badge(optional), badgeType(optional)]
    $navGroups = [
        'Dashboard' => [
            ['Dashboard', '/admin', 'layout-dashboard'],
        ],
        'AI Intelligence' => [
            ['AI News Feed', '/admin/news', 'newspaper', '24', 'info'],
            ['Breaking News', '/admin/news/breaking', 'zap', '3', 'neg'],
            ['Trending AI', '/admin/news/trending', 'trending-up'],
            ['AI Updates', '/admin/news/updates', 'refresh-cw'],
            ['Price Changes', '/admin/pricing/changes', 'tag'],
            ['Saved Intelligence', '/admin/news/saved', 'bookmark'],
            ['News Sources', '/admin/system/news-sources', 'server'],
            ['Automation Monitor', '/admin/system/automation-monitor', 'activity-square'],
        ],
        'AI Management' => [
            ['AI Tools', '/admin/tools', 'wrench'],
            ['AI Models', '/admin/models', 'brain-circuit'],
            ['AI Companies', '/admin/companies', 'building-2'],
            ['Categories', '/admin/taxonomy/categories', 'shapes'],
            ['Sub Categories', '/admin/taxonomy/subcategories', 'list-tree'],
            ['Features', '/admin/taxonomy/features', 'sparkles'],
            ['Tags', '/admin/taxonomy/tags', 'hash'],
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
            ['Reports & Abuse', '/admin/community/reports', 'shield-alert', $communityNavCounts['reports'] ?? null, 'neg', ['admin.community.reports.*'], 'Reports', 'View'],
        ],
        'Analytics' => [
            ['Website Analytics', '/admin/analytics/website', 'activity'],
            ['Tool Analytics', '/admin/analytics/tools', 'pie-chart'],
            ['Search Analytics', '/admin/analytics/search', 'search'],
            ['Comparison Analytics', '/admin/analytics/comparisons', 'git-compare'],
            ['Content Analytics', '/admin/analytics/content', 'file-bar-chart'],
            ['Trending Searches', '/admin/analytics/trending', 'flame'],
        ],
        'System' => [
            ['Notifications', '/admin/system/notifications', 'bell', '12', 'neg'],
            ['Notification Rules', '/admin/system/notification-rules', 'bell-ring'],
            ['Activity Logs', '/admin/system/activity-logs', 'scroll-text'],
            ['Roles & Permissions', '/admin/system/roles', 'shield-check', null, null, ['admin.system.roles*'], 'Security', 'View'],
            ['Security Center', '/admin/system/security', 'lock'],
            ['Two-Factor Auth', '/admin/system/2fa', 'shield-check'],
            ['Backups', '/admin/system/backups', 'database-backup'],
            ['Data Verification', '/admin/system/data-verification', 'file-check-2'],
            ['Source Reliability', '/admin/system/source-reliability', 'gauge-circle'],
            ['System Health', '/admin/system/health', 'heart-pulse'],
            ['Error Monitoring', '/admin/system/errors', 'octagon-alert', '5', 'neg'],
            ['API Monitoring', '/admin/system/api-monitoring', 'plug-zap'],
            ['Feature Flags', '/admin/system/feature-flags', 'flag-triangle-right'],
            ['SEO', '/admin/system/seo', 'search-check'],
            ['Integrations', '/admin/system/integrations', 'blocks'],
            ['Settings', '/admin/system/settings', 'settings'],
        ],
    ];
    $current = request()->path();
@endphp

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand__mark">
            <i data-lucide="orbit"></i>
        </div>
        <div class="sidebar-brand__text">
            <div class="sidebar-brand__title">AI Hub</div>
            <div class="sidebar-brand__sub">AI Research &amp; Intelligence Platform</div>
        </div>
    </div>

    <nav class="sidebar-scroll">
        @foreach ($navGroups as $group => $items)
            <div class="nav-group">
                <div class="nav-group__label">{{ $group }}</div>
                @foreach ($items as $item)
                    @php
                        [$label, $url, $icon] = $item;
                        $badge = $item[3] ?? null;
                        $badgeType = $item[4] ?? 'info';
                        $activeRoutes = $item[5] ?? [];
                        $permissionModule = $item[6] ?? null;
                        $permissionAction = $item[7] ?? null;
                        $isActive = $activeRoutes
                            ? request()->routeIs(...$activeRoutes)
                            : ($current === ltrim($url, '/') || str_starts_with($current, ltrim($url, '/').'/'));
                        $badgeClass = match ($badgeType) {
                            'warn' => 'is-warn',
                            'info' => 'is-info',
                            default => '',
                        };
                    @endphp
                    @continue($permissionModule && !auth()->user()?->canAccessModule($permissionModule, $permissionAction))
                    <a href="{{ url($url) }}" class="nav-item {{ $isActive ? 'is-active' : '' }}">
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
