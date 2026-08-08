@extends('layouts.admin')
@section('title', 'Users')

@section('content')

<x-page-header title="User Management" subtitle="{{ $users->total() }} registered users" :breadcrumb="['Users & Community', 'Users']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<form method="GET" class="filter-bar">
    <div class="input-search" style="background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sm); padding:8px 12px;">
        <i data-lucide="search"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search users by name or email...">
    </div>
    <select class="select" name="role" onchange="this.form.submit()">
        <option value="">All Roles</option>
        <option value="admin" @selected(request('role') === 'admin')>Admin</option>
        <option value="user" @selected(request('role') === 'user')>Member</option>
    </select>
    <select class="select" name="status" onchange="this.form.submit()">
        <option value="">All Status</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="suspended" @selected(request('status') === 'suspended')>Suspended</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
</form>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Reviews</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($users as $user)
        <tr>
            <td><div class="row-media"><div class="thumb">{{ substr($user->name, 0, 2) }}</div><a href="{{ route('admin.users.show', $user->id) }}"><b>{{ $user->name }}</b></a></div></td>
            <td class="text-sub">{{ $user->email }}</td>
            <td><span class="badge badge-violet">{{ ucfirst($user->role) }}</span></td>
            <td class="cell-sub">{{ $user->created_at->format('M Y') }}</td>
            <td class="mono">{{ $user->reviews_count }}</td>
            <td><x-status-badge status="{{ ucfirst($user->status) }}" type="{{ $user->status === 'active' ? 'pos' : 'neg' }}" /></td>
            <td>
                @if ($user->status === 'active')
                <form action="{{ route('admin.users.suspend', $user->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Suspend</button>
                </form>
                @else
                <form action="{{ route('admin.users.activate', $user->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Activate</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-sub" style="text-align:center; padding:32px;">No users found.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    <div class="pager">
        <span>Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users</span>
        <div class="pager-btns">{{ $users->links() }}</div>
    </div>
</div>

@endsection
