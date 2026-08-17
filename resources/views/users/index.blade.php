@extends('layouts.admin')
@section('title', 'Users')

@section('content')
<x-page-header
    title="User Management"
    subtitle="Search members, review activity and manage account access"
    :breadcrumb="['Users & Community', 'Users']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
@endif

<div class="kpi-grid">
    <x-kpi-card icon="users" label="Total Users" value="{{ number_format($stats['total']) }}" />
    <x-kpi-card icon="user-check" label="Active" value="{{ number_format($stats['active']) }}" delta="Healthy accounts" trend="up" />
    <x-kpi-card icon="user-x" label="Suspended" value="{{ number_format($stats['suspended']) }}" delta="Requires review" trend="down" />
    <x-kpi-card icon="shield-check" label="Administrators" value="{{ number_format($stats['admins']) }}" />
</div>

<form method="GET" class="filter-bar">
    <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px; flex:1; max-width:360px;">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or email...">
    </div>
    <select class="select" name="access">
        <option value="">All access levels</option>
        <option value="admin" @selected(request('access') === 'admin')>Administrator</option>
        <option value="user" @selected(request('access') === 'user')>Member</option>
    </select>
    <select class="select" name="role_id">
        <option value="">All permission roles</option>
        @foreach ($roles as $role)
            <option value="{{ $role->id }}" @selected((string) request('role_id') === (string) $role->id)>{{ $role->name }}</option>
        @endforeach
    </select>
    <select class="select" name="status">
        <option value="">All statuses</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
    </select>
    <select class="select" name="sort">
        <option value="newest" @selected(request('sort', 'newest') === 'newest')>Newest first</option>
        <option value="oldest" @selected(request('sort') === 'oldest')>Oldest first</option>
        <option value="name" @selected(request('sort') === 'name')>Name A–Z</option>
        <option value="most-active" @selected(request('sort') === 'most-active')>Most active</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm"><i data-lucide="list-filter"></i> Apply</button>
    @if (request()->query())
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">Reset</a>
    @endif
</form>

<div class="card">
    <div class="card-head">
        <div>
            <h3>User Directory</h3>
            <div class="card-head__sub">{{ number_format($users->total()) }} matching accounts</div>
        </div>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Access Type</th>
                    <th>Permission Role</th>
                    <th>Community Activity</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>
                        <div class="row-media">
                            <div class="thumb">{{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}</div>
                            <div>
                                <a href="{{ route('admin.users.show', $user->id) }}"><b>{{ $user->name }}</b></a>
                                <div class="cell-sub">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge {{ $user->role === 'admin' ? 'badge-violet' : 'badge-neutral' }}">{{ $user->role === 'admin' ? 'Administrator' : 'Member' }}</span></td>
                    <td>
                        @if (auth()->id() !== $user->id && auth()->user()->canAccessModule('Users', 'Edit') && auth()->user()->canAccessModule('Roles & Permissions', 'Edit'))
                            <form action="{{ route('admin.users.access', $user->id) }}" method="POST" class="flex gap-8" style="align-items:center;flex-wrap:wrap;" onsubmit="return confirm('Update {{ addslashes($user->name) }} access and revoke active sessions?');">
                                @csrf @method('PATCH')
                                <select class="select" name="access_level" style="min-width:125px;">
                                    <option value="user" @selected($user->role === 'user')>Member</option>
                                    <option value="admin" @selected($user->role === 'admin')>Administrator</option>
                                </select>
                                <select class="select" name="role_id" style="min-width:160px;">
                                    <option value="" disabled {{ $user->role !== 'admin' ? 'selected' : '' }}>Select permission role</option>
                                    @foreach ($roles as $role)
                                        @continue($role->isSystemRole() && !auth()->user()->isSuperAdmin())
                                        <option value="{{ $role->id }}" @selected($user->role_id == $role->id)>{{ $role->name }}{{ $role->isSystemRole() ? ' · Full access' : '' }}</option>
                                    @endforeach
                                </select>
                                <button class="icon-btn" type="submit" title="Save access"><i data-lucide="save"></i></button>
                            </form>
                        @else
                            <span class="text-sub">{{ $user->role === 'admin' ? ($user->roleModel?->name ?? 'Legacy admin — run migrations') : 'Not applicable' }}</span>
                        @endif
                    </td>
                    <td>
                        <div class="mono">{{ $user->reviews_count }} reviews · {{ $user->submissions_count }} submissions</div>
                        @if ($user->reports_received_count)
                            <div class="cell-sub" style="color:var(--warn);">{{ $user->reports_received_count }} reports received</div>
                        @endif
                    </td>
                    <td>
                        <div>{{ $user->created_at->format('M j, Y') }}</div>
                        <div class="cell-sub">{{ $user->created_at->diffForHumans() }}</div>
                    </td>
                    <td>
                        <x-status-badge
                            status="{{ ucfirst($user->status) }}"
                            type="{{ $user->status === 'active' ? 'pos' : 'neg' }}" />
                    </td>
                    <td><a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="eye"></i> Manage</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-sub" style="text-align:center; padding:40px;">No users match the selected filters.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pager">
        <span>Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }}</span>
        <div class="pager-btns">{{ $users->onEachSide(1)->links() }}</div>
    </div>
</div>
@endsection
