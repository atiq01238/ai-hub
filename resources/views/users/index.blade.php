@extends('layouts.admin')
@section('title', 'Users')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/pages/users-community.css') }}">
@endpush

@section('content')
<div class="uc-page">
    <x-page-header
        title="User Management"
        subtitle="Manage member access, permission roles, community activity and account safety."
        :breadcrumb="['Users & Community', 'Users']"
    />

    @if(session('status'))
        <div class="alert alert-success uc-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger uc-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>
    @endif

    <section class="uc-kpi-grid uc-kpi-grid--five">
        @foreach([
            ['label'=>'Total Users','value'=>$stats['total'],'icon'=>'users','tone'=>''],
            ['label'=>'Active','value'=>$stats['active'],'icon'=>'user-check','tone'=>'green'],
            ['label'=>'Suspended','value'=>$stats['suspended'],'icon'=>'user-x','tone'=>'red'],
            ['label'=>'Administrators','value'=>$stats['admins'],'icon'=>'shield-check','tone'=>'violet'],
            ['label'=>'Deleted','value'=>$stats['deleted'],'icon'=>'trash-2','tone'=>'amber'],
        ] as $item)
            <article class="uc-kpi uc-kpi--{{ $item['tone'] }}">
                <span><i data-lucide="{{ $item['icon'] }}"></i></span>
                <div><small>{{ $item['label'] }}</small><strong>{{ number_format($item['value']) }}</strong></div>
            </article>
        @endforeach
    </section>

    <form method="GET" class="card uc-filterbar">
        <div class="uc-search">
            <i data-lucide="search"></i>
            <input class="input" name="search" value="{{ request('search') }}" placeholder="Search name or email...">
        </div>
        <select class="select" name="access">
            <option value="">All access</option>
            <option value="admin" @selected(request('access')==='admin')>Administrator</option>
            <option value="user" @selected(request('access')==='user')>Member</option>
        </select>
        <select class="select" name="role_id">
            <option value="">All permission roles</option>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected((string)request('role_id')===(string)$role->id)>{{ $role->name }}</option>
            @endforeach
        </select>
        <select class="select" name="community_trust">
            <option value="">All community trust</option>
            <option value="normal" @selected(request('community_trust')==='normal')>Normal</option>
            <option value="trusted" @selected(request('community_trust')==='trusted')>Trusted</option>
            <option value="restricted" @selected(request('community_trust')==='restricted')>Restricted</option>
        </select>
        <select class="select" name="status">
            <option value="">All statuses</option>
            <option value="active" @selected(request('status')==='active')>Active</option>
            <option value="suspended" @selected(request('status')==='suspended')>Suspended</option>
            <option value="deleted" @selected(request('status')==='deleted')>Deleted</option>
        </select>
        <select class="select" name="sort">
            <option value="newest" @selected(request('sort','newest')==='newest')>Newest first</option>
            <option value="oldest" @selected(request('sort')==='oldest')>Oldest first</option>
            <option value="name" @selected(request('sort')==='name')>Name A–Z</option>
            <option value="most-active" @selected(request('sort')==='most-active')>Most active</option>
        </select>
        <button class="btn btn-secondary" type="submit"><i data-lucide="sliders-horizontal"></i>Apply</button>
        @if(request()->query())
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost"><i data-lucide="rotate-ccw"></i>Reset</a>
        @endif
    </form>

    <section class="card uc-table-card">
        <div class="uc-section-head">
            <div>
                <span class="uc-eyebrow">Identity Directory</span>
                <h2>User accounts</h2>
                <p>Account type, permissions, contribution activity and safety status.</p>
            </div>
            <span class="uc-count">{{ number_format($users->total()) }} accounts</span>
        </div>

        @if($users->count())
            <div class="table-wrap">
                <table class="data-table uc-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Access</th>
                            <th>Permission Role</th>
                            <th>Community Activity</th><th>Trust</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $user)
                        @php
                            $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn($p)=>mb_strtoupper(mb_substr($p,0,1)))->implode('');
                        @endphp
                        <tr class="{{ $user->trashed() ? 'uc-row--deleted' : '' }}">
                            <td>
                                <div class="uc-user">
                                    <a class="uc-avatar" href="{{ route('admin.users.show',$user->id) }}">{{ $initials ?: 'U' }}</a>
                                    <div>
                                        <a href="{{ route('admin.users.show',$user->id) }}"><strong>{{ $user->name }}</strong></a>
                                        <small>{{ $user->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="uc-access {{ $user->role==='admin'?'is-admin':'' }}">
                                    <i data-lucide="{{ $user->role==='admin'?'shield':'user-round' }}"></i>
                                    {{ $user->role==='admin'?'Administrator':'Member' }}
                                </span>
                            </td>
                            <td>
                                @if(! $user->trashed() && auth()->id() !== $user->id && auth()->user()->canAccessModule('Users','Edit') && auth()->user()->canAccessModule('Roles & Permissions','Edit'))
                                    <form action="{{ route('admin.users.access',$user->id) }}" method="POST" class="uc-access-form" data-user-access-form onsubmit="return confirm('Update {{ addslashes($user->name) }} access and revoke active sessions?')">
                                        @csrf @method('PATCH')
                                        <select class="select" name="access_level" data-access-level>
                                            <option value="user" @selected($user->role==='user')>Member</option>
                                            <option value="admin" @selected($user->role==='admin')>Administrator</option>
                                        </select>
                                        <select class="select" name="role_id" data-permission-role>
                                            <option value="" @selected(!$user->role_id)>Choose permission role</option>
                                            @foreach($roles as $role)
                                                @continue($role->isSystemRole() && !auth()->user()->isSuperAdmin())
                                                <option value="{{ $role->id }}" @selected($user->role_id==$role->id)>{{ $role->name }}</option>
                                            @endforeach
                                        </select>
                                        <button class="icon-btn" type="submit" title="Save access"><i data-lucide="save"></i></button>
                                    </form>
                                @else
                                    <span class="uc-muted">{{ $user->role==='admin' ? ($user->roleModel?->name ?? 'Legacy admin') : 'Not applicable' }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="uc-activity">
                                    <span><i data-lucide="star"></i>{{ $user->reviews_count }} reviews</span>
                                    <span><i data-lucide="inbox"></i>{{ $user->submissions_count }} submissions</span>
                                    @if($user->reports_received_count)
                                        <span class="is-risk"><i data-lucide="flag"></i>{{ $user->reports_received_count }} reports received</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="uc-access {{ $user->community_trust_level==='trusted'?'is-admin':'' }}">
                                    <i data-lucide="{{ $user->community_trust_level==='trusted'?'badge-check':($user->community_trust_level==='restricted'?'shield-alert':'user-check') }}"></i>
                                    {{ ucfirst($user->community_trust_level ?? 'normal') }}
                                </span>
                                <small class="uc-muted">{{ $user->community_comments_count }} comments</small>
                            </td>
                            <td><span class="uc-muted">{{ $user->created_at->format('M j, Y') }}<small>{{ $user->created_at->diffForHumans() }}</small></span></td>
                            <td>
                                @if($user->trashed())
                                    <span class="uc-deleted-badge"><i data-lucide="trash-2"></i>Deleted</span>
                                @else
                                    <x-status-badge status="{{ ucfirst($user->status) }}" type="{{ $user->status==='active'?'pos':'neg' }}" />
                                @endif
                            </td>
                            <td><a href="{{ route('admin.users.show',$user->id) }}" class="btn btn-secondary btn-sm"><i data-lucide="{{ $user->trashed() ? 'eye' : 'user-round-cog' }}"></i>{{ $user->trashed() ? 'View' : 'Manage' }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="uc-pagination"><span>Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }}</span><div>{{ $users->onEachSide(1)->links() }}</div></div>
        @else
            <div class="uc-empty"><span><i data-lucide="users"></i></span><h3>No matching users</h3><p>Adjust the current account filters and try again.</p></div>
        @endif
    </section>
</div>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/user-access-control.js') }}" defer></script>
@endpush

