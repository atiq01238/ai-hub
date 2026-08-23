@php
    $u = auth()->user();
    $initials = collect(explode(' ', trim($u->name)))->filter()->take(2)->map(fn($part) => strtoupper(substr($part,0,1)))->join('');
@endphp
<aside class="account-sidebar">
    <div class="account-profile-card">
        <div class="account-avatar">{{ $initials ?: 'U' }}</div>
        <div>
            <strong>{{ $u->name }}</strong>
            <span>{{ $u->email }}</span>
            <small>{{ ucfirst($u->status ?? 'active') }} account</small>
        </div>
    </div>

    <nav class="account-menu" aria-label="My AI Hub">
        <a class="{{ request()->routeIs('account.dashboard') ? 'active' : '' }}" href="{{ route('account.dashboard') }}"><i data-lucide="layout-dashboard"></i><span>Overview</span></a>
        <a class="{{ request()->routeIs('saved.*') ? 'active' : '' }}" href="{{ route('saved.index') }}"><i data-lucide="bookmark"></i><span>Saved Library</span></a>
        <a class="{{ request()->routeIs('account.reviews') ? 'active' : '' }}" href="{{ route('account.reviews') }}"><i data-lucide="star"></i><span>My Reviews</span></a>
        <a class="{{ request()->routeIs('account.comments') ? 'active' : '' }}" href="{{ route('account.comments') }}"><i data-lucide="message-square"></i><span>My Comments</span></a>
        <a class="{{ request()->routeIs('account.following') ? 'active' : '' }}" href="{{ route('account.following') }}"><i data-lucide="bell-plus"></i><span>Following</span></a>
        <a class="{{ request()->routeIs('user.comparisons.index') ? 'active' : '' }}" href="{{ route('user.comparisons.index') }}"><i data-lucide="scale"></i><span>Saved Comparisons</span></a>
        <a class="{{ request()->routeIs('user.comparisons.history') ? 'active' : '' }}" href="{{ route('user.comparisons.history') }}"><i data-lucide="history"></i><span>Comparison History</span></a>
        <a class="{{ request()->routeIs('user.testlab.history') ? 'active' : '' }}" href="{{ route('user.testlab.history') }}"><i data-lucide="flask-conical"></i><span>Test Lab History</span></a>
        <a class="{{ request()->routeIs('account.activity') ? 'active' : '' }}" href="{{ route('account.activity') }}"><i data-lucide="activity"></i><span>Recent Activity</span></a>
        <a class="{{ request()->routeIs('account.notifications*') ? 'active' : '' }}" href="{{ route('account.notifications') }}"><i data-lucide="bell"></i><span>Notifications</span></a>
        <a class="{{ request()->routeIs('account.email-preferences*') ? 'active' : '' }}" href="{{ route('account.email-preferences') }}"><i data-lucide="mail"></i><span>Email Preferences</span></a>
        <a class="{{ request()->routeIs('account.settings') ? 'active' : '' }}" href="{{ route('account.settings') }}"><i data-lucide="settings"></i><span>Settings & Security</span></a>
    </nav>

    <div class="account-sidebar-foot">
        <a href="{{ route('tools.index') }}"><i data-lucide="sparkles"></i>Discover AI tools</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"><i data-lucide="log-out"></i>Sign out</button>
        </form>
    </div>
</aside>
