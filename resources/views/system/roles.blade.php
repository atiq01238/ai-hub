@extends('layouts.admin')
@section('title','Roles & Permissions')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/administration.css') }}">@endpush
@section('content')
@php
$totalPermissionSlots=collect($modules)->sum(fn($supported)=>count($supported));
$systemRole=$selectedRole?->isSystemRole()??false;
$allowedCount=$systemRole?$totalPermissionSlots:($selectedRole?->permissions?->where('allowed',true)->count()??0);
$coverage=$totalPermissionSlots?round(($allowedCount/$totalPermissionSlots)*100):0;
@endphp
<div class="ad-page">
<x-page-header title="Roles & Permissions" subtitle="Apply least-privilege access across every administrator and protected action." :breadcrumb="['System','Roles & Permissions']" />
@if(session('status'))<div class="alert alert-success ad-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger ad-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif

<section class="ad-kpis">
@foreach([['Roles',$roles->count(),'shield',''],['Selected Role Users',$selectedRole?->users?->count()??0,'users','cyan'],['Allowed Permissions',$allowedCount,'key-round','violet'],['Permission Coverage',$coverage.'%','gauge','green']] as [$label,$value,$icon,$tone])
<article class="ad-kpi ad-kpi--{{ $tone }}"><span><i data-lucide="{{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ $value }}</strong></div></article>
@endforeach
</section>

@if(auth()->user()->canAccessModule('Roles & Permissions','Add'))
<section class="card ad-create-role">
<div><span class="ad-eyebrow">Least-Privilege Provisioning</span><h2>Create permission role</h2><p>New roles are created with every permission disabled.</p></div>
<form action="{{ route('admin.system.roles.store') }}" method="POST">@csrf
<label><span>Role Name</span><input class="input" name="name" placeholder="e.g. Tool Manager" required></label>
<label class="ad-color-field"><span>Accent</span><input type="color" name="color" value="#5b7fff"></label>
<button class="btn btn-primary" type="submit"><i data-lucide="plus"></i>Create Role</button>
</form>
</section>
@endif

<section class="ad-role-grid">
@forelse($roles as $role)
<a href="{{ route('admin.system.roles',['role_id'=>$role->id]) }}" class="card ad-role-card {{ $selectedRole && $selectedRole->id===$role->id?'is-selected':'' }}" style="--role-color:{{ $role->color }}">
<div class="ad-role-card__top"><span class="ad-role-dot"></span>@if($role->isSystemRole())<span class="ad-badge is-system"><i data-lucide="crown"></i>System · Full Access</span>@else<span class="ad-badge">Custom Role</span>@endif</div>
<h3>{{ $role->name }}</h3><p>{{ $role->slug }}</p><div class="ad-role-card__foot"><span><i data-lucide="users"></i>{{ $role->users_count }} {{ Str::plural('user',$role->users_count) }}</span><i data-lucide="arrow-right"></i></div>
</a>
@empty<div class="card ad-empty ad-empty--wide"><span><i data-lucide="shield-plus"></i></span><h3>No granular roles yet</h3><p>Create the first custom administrator role above.</p></div>@endforelse
</section>

@if($selectedRole)
<div class="ad-role-layout">
<main class="ad-role-main">
@if(auth()->user()->canAccessModule('Roles & Permissions','Edit')&&!$selectedRole->isSystemRole())
<section class="card ad-panel">
<header class="ad-card-head"><div><span class="ad-eyebrow">Role Identity</span><h2>{{ $selectedRole->name }}</h2><p>Rename or recolor without changing assigned users.</p></div><i data-lucide="badge"></i></header>
<form action="{{ route('admin.system.roles.update',$selectedRole->id) }}" method="POST" class="ad-inline-form">@csrf @method('PUT')
<label><span>Name</span><input class="input" name="name" value="{{ $selectedRole->name }}" required></label>
<label class="ad-color-field"><span>Color</span><input type="color" name="color" value="{{ $selectedRole->color }}"></label>
<button class="btn btn-secondary"><i data-lucide="save"></i>Save Identity</button>
</form>
</section>
@endif

<section class="card ad-panel">
<header class="ad-card-head"><div><span class="ad-eyebrow">Access Matrix</span><h2>Permission matrix · {{ $selectedRole->name }}</h2><p>{{ $selectedRole->isSystemRole()?'Protected system role — every supported permission is permanently enabled.':'View controls page/sidebar access; action permissions protect writes and exports.' }}</p></div><span class="ad-coverage">{{ $coverage }}% coverage</span></header>
<form action="{{ route('admin.system.roles.permissions.update',$selectedRole->id) }}" method="POST" id="permissionForm">@csrf @method('PUT')
@if(!$selectedRole->isSystemRole() && auth()->user()->canAccessModule('Roles & Permissions','Edit'))
<div class="ad-matrix-tools"><button type="button" class="btn btn-ghost btn-sm" onclick="toggleAction('View',true)">Enable all View</button><button type="button" class="btn btn-ghost btn-sm" onclick="toggleAction('View',false)">Clear all View</button><button type="button" class="btn btn-ghost btn-sm" onclick="clearAllPermissions()">Clear everything</button></div>
@else
<div class="ad-protected"><i data-lucide="lock-keyhole"></i><span>Super Admin bypasses the permission matrix and cannot be restricted.</span></div>
@endif
<div class="table-wrap"><table class="data-table ad-matrix"><thead><tr><th>Module / Section</th>@foreach($actions as $a)<th>{{ $a }}</th>@endforeach</tr></thead><tbody>
@foreach($modules as $module=>$supportedActions)
<tr><td><strong>{{ $module }}</strong><small>{{ in_array('View',$supportedActions,true)?'View controls navigation and protected GET access':'' }}</small></td>
@foreach($actions as $action)
@php $supported=in_array($action,$supportedActions,true); $on=$supported&&($selectedRole->isSystemRole()||$selectedRole->can($module,$action)); @endphp
<td class="{{ !$supported?'is-disabled':'' }}">
@if($supported)
<label class="ad-permission-toggle" title="{{ $module }} → {{ $action }}"><input type="checkbox" data-action="{{ $action }}" name="permissions[]" value="{{ $module }}|{{ $action }}" {{ $on?'checked':'' }} {{ (auth()->user()->canAccessModule('Roles & Permissions','Edit')&&!$selectedRole->isSystemRole())?'':'disabled' }}><span><i></i></span></label>
@else<span>—</span>@endif
</td>
@endforeach</tr>
@endforeach
</tbody></table></div>
@if(auth()->user()->canAccessModule('Roles & Permissions','Edit')&&!$selectedRole->isSystemRole())
<footer class="ad-matrix-foot"><p><strong>Important:</strong> disabling View hides linked navigation and blocks protected page access.</p><button class="btn btn-primary" type="submit"><i data-lucide="shield-check"></i>Save Permissions</button></footer>
@endif
</form>
</section>
</main>

<aside class="ad-role-aside">
<section class="card ad-side-card"><span class="ad-eyebrow">Assigned Administrators</span><div class="ad-assigned">
@forelse($selectedRole->users->take(12) as $assignedUser)
<a href="{{ route('admin.users.show',$assignedUser->id) }}"><span><i data-lucide="user-round"></i></span><div><strong>{{ $assignedUser->name }}</strong><small>{{ $assignedUser->email }}</small></div><i data-lucide="arrow-up-right"></i></a>
@empty<div class="ad-empty ad-empty--small"><p>No users assigned to this role.</p></div>@endforelse
</div>
@if($selectedRole->users->count()>12)<p class="ad-more">+ {{ $selectedRole->users->count()-12 }} more administrators</p>@endif
<a href="{{ route('admin.users.index',['role_id'=>$selectedRole->id]) }}" class="btn btn-secondary ad-full"><i data-lucide="users"></i>View Assigned Users</a>
</section>
<section class="card ad-side-card"><span class="ad-eyebrow">Access Architecture</span><div class="ad-architecture"><i data-lucide="user-round"></i><span>Member / Administrator</span><i data-lucide="arrow-down"></i><i data-lucide="shield"></i><span>Permission Role</span><i data-lucide="arrow-down"></i><i data-lucide="key-round"></i><span>Module + Action Matrix</span></div><p>Super Admin is protected and always receives full access.</p></section>
@if(auth()->user()->canAccessModule('Roles & Permissions','Delete')&&!$selectedRole->isSystemRole())
<section class="card ad-danger"><span class="ad-eyebrow">Danger Zone</span><h3>Delete role</h3><p>A role cannot be deleted while administrators remain assigned.</p><form action="{{ route('admin.system.roles.destroy',$selectedRole->id) }}" method="POST" onsubmit="return confirm('Delete the {{ addslashes($selectedRole->name) }} role?')">@csrf @method('DELETE')<button class="btn btn-danger ad-full" {{ $selectedRole->users->count()?'disabled':'' }}><i data-lucide="trash-2"></i>Delete Role</button></form></section>
@endif
</aside>
</div>
@endif
</div>
<script>
function toggleAction(action,checked){document.querySelectorAll('#permissionForm input[data-action="'+action+'"]:not(:disabled)').forEach(el=>el.checked=checked)}
function clearAllPermissions(){document.querySelectorAll('#permissionForm input[type="checkbox"]:not(:disabled)').forEach(el=>el.checked=false)}
</script>
@endsection
