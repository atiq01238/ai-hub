@php
    use App\Models\AppNotification;
    use Illuminate\Support\Facades\Schema;

    $headerUser = auth()->user();
    $headerInitials = collect(preg_split('/\s+/', trim($headerUser?->name ?? 'Admin')))
        ->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    $headerRole = $headerUser?->roleModel?->name
        ?? ($headerUser?->role === 'admin' ? 'Administrator' : ucfirst($headerUser?->role ?? 'Account'));

    $headerNotifications = collect();
    $headerUnreadCount = 0;

    try {
        if ($headerUser && Schema::hasTable('notifications')) {
            $noticeScope = fn ($query) => $query
                ->where('user_id', $headerUser->id)
                ->orWhereNull('user_id');

            $headerUnreadCount = AppNotification::where($noticeScope)->unread()->count();
            $headerNotifications = AppNotification::where($noticeScope)->latest()->limit(5)->get();
        }
    } catch (\Throwable $e) {
        $headerNotifications = collect();
        $headerUnreadCount = 0;
    }

    $quickAddItems = collect([
        ['AI Tool','admin.tools.create','wrench','AI Tools','Add'],
        ['AI Model','admin.models.create','brain-circuit','AI Models','Add'],
        ['Company','admin.companies.create','building-2','AI Companies','Add'],
        ['News','admin.news.create','newspaper','AI News','Add'],
        ['Comparison','admin.comparisons.builder','columns-3','Comparisons','Add'],
        ['Benchmark','admin.benchmarks.create','bar-chart-3','Benchmarks','Add'],
        ['Article','admin.content.articles.editor.create','file-text','Content','Add'],
        ['Editorial Review','admin.content.reviews.editor','star','Reviews','Add'],
    ])->filter(fn ($item) => $headerUser?->canAccessModule($item[3], $item[4]));

    $navigationItems = collect([
        ['Dashboard','admin.dashboard','layout-dashboard','Dashboard','View'],
        ['AI News','admin.news.index','newspaper','AI News','View'],
        ['AI Tools','admin.tools.index','wrench','AI Tools','View'],
        ['AI Models','admin.models.index','brain-circuit','AI Models','View'],
        ['AI Companies','admin.companies.index','building-2','AI Companies','View'],
        ['Comparisons','admin.comparisons.index','columns-3','Comparisons','View'],
        ['AI Test Lab','admin.testlab.index','flask-conical','AI Test Lab','View'],
        ['Benchmarks','admin.benchmarks.index','bar-chart-3','Benchmarks','View'],
        ['Pricing','admin.pricing.index','credit-card','Pricing','View'],
        ['Articles','admin.content.articles.index','file-text','Content','View'],
        ['Reviews','admin.content.reviews.index','message-square-heart','Reviews','View'],
        ['Users','admin.users.index','users','Users','View'],
        ['Analytics','admin.analytics.website','chart-no-axes-combined','Analytics','View'],
        ['System Overview','admin.system.overview','command','Security','View'],
        ['Security Center','admin.system.security','shield-check','Security','View'],
        ['Settings','admin.system.settings','settings','Settings','View'],
    ])->filter(fn ($item) => $headerUser?->canAccessModule($item[3], $item[4]));
@endphp

<header class="topbar">
    <button class="icon-btn topbar-menu-btn" id="sidebarToggle" type="button" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false">
        <i data-lucide="panel-left"></i>
    </button>

    <button class="global-search" type="button" data-bs-toggle="modal" data-bs-target="#globalSearchModal" aria-label="Quick navigation">
        <i data-lucide="search"></i>
        <span>Quick navigation...</span>
        <span class="kbd">Ctrl K</span>
    </button>

    <div class="topbar-right">
        @if($headerUser?->canAccessModule('Security','View'))
        <a href="{{ route('admin.system.overview') }}" class="console-pill" title="Open system overview">
            <span class="console-pill__dot"></span>
            <span>System Console</span>
        </a>
        @endif

        @if($quickAddItems->isNotEmpty())
        <div class="topbar-popover">
            <button class="btn btn-primary btn-sm" type="button" data-dropdown-toggle="#quickAddMenu" aria-expanded="false">
                <i data-lucide="plus"></i><span>Quick Add</span>
            </button>
            <div class="dropdown-menu-custom quick-add-menu" id="quickAddMenu">
                <div class="dropdown-menu-title">
                    <span>Create New</span>
                    <small>Permission-aware shortcuts</small>
                </div>
                @foreach($quickAddItems as $item)
                    <a href="{{ route($item[1]) }}"><i data-lucide="{{ $item[2] }}"></i><span>{{ $item[0] }}</span></a>
                @endforeach
            </div>
        </div>
        @endif

        @if($headerUser?->canAccessModule('Notifications','View'))
        <div class="topbar-popover">
            <button class="icon-btn" type="button" data-dropdown-toggle="#notifMenu" aria-label="Notifications" aria-expanded="false">
                <i data-lucide="bell"></i>
                @if($headerUnreadCount > 0)<span class="badge-dot"></span>@endif
            </button>
            <div class="dropdown-menu-custom dropdown-menu-wide header-notifications" id="notifMenu">
                <div class="dropdown-menu-title">
                    <span>Notifications</span>
                    <small>{{ $headerUnreadCount ? $headerUnreadCount.' unread' : 'All caught up' }}</small>
                </div>
                @forelse($headerNotifications as $notice)
                    <a href="{{ $notice->action_url ?: route('admin.system.notifications') }}" class="notif-row {{ $notice->read_at ? '' : 'is-unread' }}">
                        <span class="notif-row__icon tone-{{ $notice->tone }}"><i data-lucide="{{ $notice->icon ?: 'bell' }}"></i></span>
                        <div>
                            <strong>{{ $notice->title }}</strong>
                            @if($notice->description)<p>{{ \Illuminate\Support\Str::limit($notice->description, 86) }}</p>@endif
                            <small>{{ $notice->created_at->diffForHumans() }}</small>
                        </div>
                    </a>
                @empty
                    <div class="header-empty-state"><i data-lucide="bell-off"></i><span>No notifications yet.</span></div>
                @endforelse
                <a href="{{ route('admin.system.notifications') }}" class="dropdown-menu-footer">Open Notification Center <i data-lucide="arrow-right"></i></a>
            </div>
        </div>
        @endif

        <div class="topbar-popover">
            <button class="avatar" type="button" data-dropdown-toggle="#profileMenu" aria-label="Account menu" aria-expanded="false">{{ $headerInitials ?: 'A' }}</button>
            <div class="dropdown-menu-custom profile-menu" id="profileMenu">
                <div class="profile-menu__identity">
                    <span class="avatar avatar--large">{{ $headerInitials ?: 'A' }}</span>
                    <div><strong>{{ $headerUser?->name ?? 'Administrator' }}</strong><small>{{ $headerRole }}</small><span>{{ $headerUser?->email }}</span></div>
                </div>
                <div class="dropdown-divider-custom"></div>
                <a href="{{ route('admin.system.2fa') }}"><i data-lucide="shield-check"></i><span>Two-Factor Authentication</span></a>
                @if($headerUser?->canAccessModule('Security','View'))
                    <a href="{{ route('admin.system.security') }}"><i data-lucide="lock-keyhole"></i><span>Security Center</span></a>
                @endif
                @if($headerUser?->canAccessModule('Settings','View'))
                    <a href="{{ route('admin.system.settings') }}"><i data-lucide="settings"></i><span>Settings</span></a>
                @endif
                <div class="dropdown-divider-custom"></div>
                <form action="{{ route('logout') }}" method="POST" class="profile-logout-form">@csrf
                    <button type="submit"><i data-lucide="log-out"></i><span>Sign Out</span></button>
                </form>
            </div>
        </div>
    </div>
</header>

<div class="modal fade admin-nav-modal" id="globalSearchModal" tabindex="-1" aria-labelledby="globalSearchLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content admin-nav-modal__content">
            <div class="admin-nav-modal__search">
                <i data-lucide="search"></i>
                <input id="adminNavSearch" type="search" autocomplete="off" placeholder="Find an admin section..." aria-label="Find an admin section">
                <span class="kbd">Esc</span>
            </div>
            <div class="admin-nav-modal__body">
                <div class="admin-nav-modal__label">Available Navigation</div>
                <div class="admin-nav-results">
                    @foreach($navigationItems as $item)
                        <a href="{{ route($item[1]) }}" data-nav-search-item>
                            <span><i data-lucide="{{ $item[2] }}"></i></span>
                            <div><strong>{{ $item[0] }}</strong><small>{{ $item[3] }}</small></div>
                            <i data-lucide="arrow-up-right"></i>
                        </a>
                    @endforeach
                </div>
                <div class="header-empty-state admin-nav-empty" id="adminNavSearchEmpty" hidden>
                    <i data-lucide="search-x"></i><span>No matching admin section.</span>
                </div>
            </div>
        </div>
    </div>
</div>
