<header class="topbar">
    <button class="icon-btn" id="sidebarToggle" aria-label="Toggle sidebar">
        <i data-lucide="panel-left"></i>
    </button>

    <div class="global-search" data-bs-toggle="modal" data-bs-target="#globalSearchModal">
        <i data-lucide="search"></i>
        <span>Search tools, models, companies, news...</span>
        <span class="kbd">Ctrl K</span>
    </div>

    <div class="topbar-right">
        <div class="status-pill">
            <span class="status-dot pulse"></span> All Systems Operational
        </div>

        <div style="position:relative;">
            <button class="btn btn-primary btn-sm" data-dropdown-toggle="#quickAddMenu">
                <i data-lucide="plus"></i> Quick Add
            </button>
            <div class="dropdown-menu-custom" id="quickAddMenu">
                <a href="{{ url('/tools/create') }}"><i data-lucide="wrench"></i> Add AI Tool</a>
                <a href="{{ url('/models/create') }}"><i data-lucide="brain-circuit"></i> Add AI Model</a>
                <a href="{{ url('/companies/create') }}"><i data-lucide="building-2"></i> Add Company</a>
                <a href="{{ url('/news/create') }}"><i data-lucide="newspaper"></i> Add News</a>
                <a href="{{ url('/comparisons/builder') }}"><i data-lucide="square-stack"></i> Create Comparison</a>
                <a href="{{ url('/benchmarks/create') }}"><i data-lucide="bar-chart-3"></i> Create Benchmark</a>
                <a href="{{ url('/content/articles/editor') }}"><i data-lucide="file-text"></i> Create Article</a>
                <a href="{{ url('/content/reviews/editor') }}"><i data-lucide="star"></i> Add Review</a>
                <a href="{{ url('/system/api-monitoring') }}"><i data-lucide="server"></i> Add Source</a>
            </div>
        </div>

        <div style="position:relative;">
            <button class="icon-btn" data-dropdown-toggle="#notifMenu" aria-label="Notifications">
                <i data-lucide="bell"></i>
                <span class="badge-dot"></span>
            </button>
            <div class="dropdown-menu-custom dropdown-menu-wide" id="notifMenu">
                <div class="dropdown-menu-title">Notifications <span class="text-muted" style="font-weight:400;">12 new</span></div>
                <a href="{{ url('/system/notifications') }}" class="notif-row">
                    <i data-lucide="zap" style="color:var(--neg)"></i>
                    <div><b>Breaking:</b> OpenAI ships GPT-5.2 Turbo <span class="cell-sub">2 min ago</span></div>
                </a>
                <a href="{{ url('/system/notifications') }}" class="notif-row">
                    <i data-lucide="tag" style="color:var(--warn)"></i>
                    <div>Anthropic changed Claude API pricing <span class="cell-sub">18 min ago</span></div>
                </a>
                <a href="{{ url('/system/notifications') }}" class="notif-row">
                    <i data-lucide="server-crash" style="color:var(--neg)"></i>
                    <div>News source "TechCrunch AI" failed to fetch <span class="cell-sub">1 hr ago</span></div>
                </a>
                <a href="{{ url('/system/notifications') }}" class="dropdown-menu-footer">View all notifications</a>
            </div>
        </div>

        <button class="icon-btn" aria-label="Help"><i data-lucide="circle-help"></i></button>
        <button class="icon-btn" aria-label="Toggle theme"><i data-lucide="moon-star"></i></button>

        <div style="position:relative;">
            <div class="avatar" data-dropdown-toggle="#profileMenu">SA</div>
            <div class="dropdown-menu-custom" id="profileMenu" style="right:0; left:auto;">
                <div class="dropdown-menu-title">Sarah Ahmed <span class="text-muted" style="font-weight:400; display:block; font-size:11px;">Super Admin</span></div>
                <a href="#"><i data-lucide="user"></i> My Profile</a>
                <a href="{{ url('/system/security') }}"><i data-lucide="lock"></i> Security Center</a>
                <a href="{{ url('/system/settings') }}"><i data-lucide="settings"></i> Settings</a>
                <a href="#"><i data-lucide="log-out"></i> Sign Out</a>
            </div>
        </div>
    </div>
</header>

<div class="modal fade" id="globalSearchModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background:var(--bg-elevated); border:1px solid var(--border); border-radius:var(--radius-lg);">
            <div style="padding:16px 18px; border-bottom:1px solid var(--border-soft);">
                <div class="input-search" style="background:var(--surface); border-radius:var(--radius-sm); padding:10px 14px; border:1px solid var(--border);">
                    <i data-lucide="search"></i>
                    <input type="text" placeholder="Search AI Tools, Models, Companies, News, Articles, Comparisons, Users, Reviews...">
                </div>
            </div>
            <div class="modal-body">
                <div class="text-muted" style="font-size:12px; margin-bottom:10px;">SUGGESTED</div>
                <div class="filter-bar" style="margin-bottom:0;">
                    <span class="chip">ChatGPT</span>
                    <span class="chip">Claude Opus 4.8</span>
                    <span class="chip">Midjourney v7</span>
                    <span class="chip">Anthropic</span>
                    <span class="chip">Pricing changes this week</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dropdown-menu-custom{
    display:none; position:absolute; top:calc(100% + 10px); left:0; min-width:230px;
    background:var(--bg-elevated); border:1px solid var(--border); border-radius:var(--radius-md);
    box-shadow:var(--shadow-pop); padding:8px; z-index:60;
}
.dropdown-menu-custom.is-open{ display:block; }
.dropdown-menu-custom a{ display:flex; align-items:center; gap:10px; padding:9px 10px; border-radius:7px; font-size:13px; color:var(--text-md); }
.dropdown-menu-custom a:hover{ background:var(--surface-hover); color:var(--text-hi); }
.dropdown-menu-custom a svg{ width:15px; height:15px; }
.dropdown-menu-title{ padding:8px 10px 10px; font-size:13px; font-weight:700; color:var(--text-hi); border-bottom:1px solid var(--border-soft); margin-bottom:6px; }
.dropdown-menu-wide{ min-width:320px; }
.notif-row{ display:flex; align-items:flex-start; gap:10px; padding:10px; border-radius:8px; font-size:12.5px; color:var(--text-md); }
.notif-row div{ line-height:1.4; }
.notif-row svg{ width:16px; height:16px; margin-top:2px; flex-shrink:0; }
.notif-row:hover{ background:var(--surface-hover); }
.dropdown-menu-footer{ text-align:center; font-size:12px; font-weight:600; color:var(--brand-3); padding:9px; }
</style>
