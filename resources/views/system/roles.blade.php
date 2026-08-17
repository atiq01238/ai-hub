@extends('layouts.admin')
@section('title', 'Roles & Permissions')

@section('content')
<x-page-header title="Roles &amp; Permissions" subtitle="Assign least-privilege access to every administrator" :breadcrumb="['System', 'Roles & Permissions']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" style="margin-bottom:16px;">{{ $errors->first() }}</div>
@endif

@php
    $totalPermissionSlots = collect($modules)->sum(fn($supported) => count($supported));
    $systemRole = $selectedRole?->isSystemRole() ?? false;
    $allowedCount = $systemRole ? $totalPermissionSlots : ($selectedRole?->permissions?->where('allowed', true)->count() ?? 0);
    $coverage = $totalPermissionSlots ? round(($allowedCount / $totalPermissionSlots) * 100) : 0;
@endphp

<div class="grid-4" style="margin-bottom:16px;">
    <div class="card card-pad"><div class="cell-sub">Roles</div><div style="font-size:26px;font-weight:800;">{{ $roles->count() }}</div></div>
    <div class="card card-pad"><div class="cell-sub">Selected Role Users</div><div style="font-size:26px;font-weight:800;">{{ $selectedRole?->users?->count() ?? 0 }}</div></div>
    <div class="card card-pad"><div class="cell-sub">Allowed Permissions</div><div style="font-size:26px;font-weight:800;">{{ $allowedCount }}</div></div>
    <div class="card card-pad"><div class="cell-sub">Permission Coverage</div><div style="font-size:26px;font-weight:800;">{{ $coverage }}%</div></div>
</div>

@if (auth()->user()->canAccessModule('Roles & Permissions', 'Add'))
<div class="card card-pad" style="margin-bottom:16px;">
    <div class="section-title" style="margin-bottom:12px;">Create Permission Role</div>
    <form action="{{ route('admin.system.roles.store') }}" method="POST" class="flex gap-8" style="align-items:flex-end;flex-wrap:wrap;">
        @csrf
        <div class="form-field" style="min-width:240px;"><label>Role Name</label><input class="input" name="name" placeholder="e.g. Tool Manager" required></div>
        <div class="form-field"><label>Accent</label><input class="input" type="color" name="color" value="#5b7fff" style="width:64px;padding:4px;"></div>
        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Create Role</button>
        <span class="cell-sub">New roles start with no permissions.</span>
    </form>
</div>
@endif

<div class="grid-3" style="margin-bottom:20px;">
    @forelse ($roles as $role)
    <a href="{{ route('admin.system.roles', ['role_id' => $role->id]) }}" style="text-decoration:none;color:inherit;">
        <div class="card card-pad" style="border-left:3px solid {{ $role->color }};{{ $selectedRole && $selectedRole->id === $role->id ? 'outline:2px solid var(--brand-1);' : '' }}">
            <div class="flex items-center justify-between" style="gap:12px;">
                <div><b style="font-size:14px;">{{ $role->name }}</b> @if($role->isSystemRole())<span class="badge badge-violet" style="margin-left:6px;">System · Full Access</span>@endif<div class="cell-sub">{{ $role->slug }}</div></div>
                <span class="badge badge-neutral">{{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}</span>
            </div>
        </div>
    </a>
    @empty
    <div class="card card-pad text-sub" style="grid-column:1/-1;text-align:center;">No granular roles yet. Full-access administrators can create the first role above.</div>
    @endforelse
</div>

@if ($selectedRole)
<div class="grid-3" style="align-items:start;">
    <div style="grid-column:span 2;">
        @if (auth()->user()->canAccessModule('Roles & Permissions', 'Edit') && !$selectedRole->isSystemRole())
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="flex items-center justify-between" style="gap:16px;flex-wrap:wrap;">
                <div><div class="section-title">Role Identity</div><div class="cell-sub">Rename or recolor this role without changing assigned users.</div></div>
                <form action="{{ route('admin.system.roles.update', $selectedRole->id) }}" method="POST" class="flex gap-8" style="align-items:flex-end;flex-wrap:wrap;">
                    @csrf @method('PUT')
                    <div class="form-field"><label>Name</label><input class="input" name="name" value="{{ $selectedRole->name }}" required></div>
                    <div class="form-field"><label>Color</label><input class="input" type="color" name="color" value="{{ $selectedRole->color }}" style="width:64px;padding:4px;"></div>
                    <button class="btn btn-secondary btn-sm"><i data-lucide="save"></i> Save Role</button>
                </form>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-head">
                <div><h3>Permission Matrix — {{ $selectedRole->name }}</h3><div class="card-head__sub">{{ $selectedRole->isSystemRole() ? 'Protected system role — every permission is permanently enabled.' : 'View controls section visibility. Add/Edit/Delete/Publish/Export control protected actions.' }}</div></div>
            </div>

            <form action="{{ route('admin.system.roles.permissions.update', $selectedRole->id) }}" method="POST" id="permissionForm">
                @csrf @method('PUT')
                @if(!$selectedRole->isSystemRole())
                <div style="padding:12px 20px;border-bottom:1px solid var(--border-soft);" class="flex gap-8">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleAction('View', true)">Enable all View</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleAction('View', false)">Clear all View</button>
                    <button type="button" class="btn btn-ghost btn-sm" onclick="clearAllPermissions()">Clear everything</button>
                </div>
                @else
                <div style="padding:12px 20px;border-bottom:1px solid var(--border-soft);" class="alert alert-success">Super Admin bypasses the permission matrix and can access every admin section and action.</div>
                @endif
                <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Module / Section</th>@foreach($actions as $a)<th style="text-align:center;">{{ $a }}</th>@endforeach</tr></thead>
                        <tbody>
                        @foreach($modules as $module => $supportedActions)
                            <tr>
                                <td><b>{{ $module }}</b><div class="cell-sub">{{ in_array('View', $supportedActions, true) ? 'Sidebar/page access controlled by View' : '' }}</div></td>
                                @foreach($actions as $action)
                                    @php
                                        $supported = in_array($action, $supportedActions, true);
                                        $on = $supported && ($selectedRole->isSystemRole() || $selectedRole->can($module, $action));
                                    @endphp
                                    <td style="text-align:center;{{ !$supported ? 'opacity:.28;' : '' }}">
                                        @if ($supported)
                                            <label style="cursor:pointer;display:inline-block;" title="{{ $module }} → {{ $action }}">
                                                <input type="checkbox" data-action="{{ $action }}" name="permissions[]" value="{{ $module }}|{{ $action }}" class="switch-checkbox" {{ $on ? 'checked' : '' }} {{ (auth()->user()->canAccessModule('Roles & Permissions', 'Edit') && !$selectedRole->isSystemRole()) ? '' : 'disabled' }}>
                                                <span class="switch" style="margin:0 auto;"><i></i></span>
                                            </label>
                                        @else
                                            <span class="cell-sub">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if (auth()->user()->canAccessModule('Roles & Permissions', 'Edit') && !$selectedRole->isSystemRole())
                <div style="padding:14px 20px;" class="flex items-center justify-between">
                    <div class="cell-sub"><b>Important:</b> turning View off hides the linked navigation item and blocks its protected GET route.</div>
                    <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="shield-check"></i> Save Permissions</button>
                </div>
                @endif
            </form>
        </div>
    </div>

    <div>
        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="flex items-center justify-between" style="margin-bottom:12px;"><div class="section-title">Assigned Users</div><span class="badge badge-neutral">{{ $selectedRole->users->count() }}</span></div>
            @forelse($selectedRole->users->take(12) as $assignedUser)
                <div class="flex items-center justify-between" style="padding:9px 0;border-bottom:1px solid var(--border-soft);gap:12px;">
                    <div><b style="font-size:12.5px;">{{ $assignedUser->name }}</b><div class="cell-sub">{{ $assignedUser->email }}</div></div>
                    <a href="{{ route('admin.users.show', $assignedUser->id) }}" class="icon-btn" title="Manage user"><i data-lucide="external-link"></i></a>
                </div>
            @empty
                <div class="cell-sub" style="padding:12px 0;">No users are assigned to this role.</div>
            @endforelse
            @if($selectedRole->users->count() > 12)<div class="cell-sub" style="margin-top:10px;">+ {{ $selectedRole->users->count() - 12 }} more users</div>@endif
            <a href="{{ route('admin.users.index', ['role_id' => $selectedRole->id]) }}" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;margin-top:12px;"><i data-lucide="users"></i> View users with this role</a>
        </div>

        <div class="card card-pad" style="margin-bottom:16px;">
            <div class="section-title">How access works</div>
            <div class="cell-sub" style="line-height:1.65;margin-top:8px;"><b>Access Type</b> decides whether an account is a Member or Administrator. Every Administrator must have a <b>Permission Role</b>. <b>Super Admin</b> is protected and always has full access; all other roles follow the matrix above.</div>
        </div>

        @if (auth()->user()->canAccessModule('Roles & Permissions', 'Delete') && !$selectedRole->isSystemRole())
        <div class="card card-pad" style="border-color:rgba(239,68,68,.35);">
            <div class="section-title">Danger Zone</div>
            <p class="cell-sub" style="margin:8px 0 12px;">A role cannot be deleted while users are assigned to it.</p>
            <form action="{{ route('admin.system.roles.destroy', $selectedRole->id) }}" method="POST" onsubmit="return confirm('Delete the {{ addslashes($selectedRole->name) }} role?');">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm" {{ $selectedRole->users->count() ? 'disabled' : '' }}><i data-lucide="trash-2"></i> Delete Role</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endif

<script>
function toggleAction(action, checked) {
    document.querySelectorAll('#permissionForm input[data-action="' + action + '"]:not(:disabled)').forEach(el => el.checked = checked);
}
function clearAllPermissions() {
    document.querySelectorAll('#permissionForm input[type="checkbox"]:not(:disabled)').forEach(el => el.checked = false);
}
</script>
@endsection
