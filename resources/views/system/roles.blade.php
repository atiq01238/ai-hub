@extends('layouts.admin')
@section('title', 'Roles & Permissions')

@section('content')

<x-page-header title="Roles &amp; Permissions" subtitle="{{ $roles->count() }} roles" :breadcrumb="['System', 'Roles & Permissions']" />

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif

<div class="card card-pad" style="margin-bottom:16px;">
    <form action="{{ route('admin.system.roles.store') }}" method="POST" class="flex gap-8" style="align-items:flex-end;">
        @csrf
        <div class="form-field"><label>New Role Name</label><input class="input" name="name" placeholder="e.g. Editor" required></div>
        <div class="form-field"><label>Color</label><input class="input" type="color" name="color" value="#5b7fff" style="width:60px; padding:4px;"></div>
        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Role</button>
    </form>
</div>

<div class="grid-3" style="margin-bottom:20px;">
    @forelse ($roles as $role)
    <a href="{{ route('admin.system.roles', ['role_id' => $role->id]) }}" style="text-decoration:none; color:inherit;">
        <div class="card card-pad" style="border-left:3px solid {{ $role->color }}; {{ $selectedRole && $selectedRole->id === $role->id ? 'outline:2px solid var(--brand-1);' : '' }}">
            <div class="flex items-center justify-between">
                <b style="font-size:14px;">{{ $role->name }}</b>
                <span class="cell-sub">{{ $role->users_count }} users</span>
            </div>
        </div>
    </a>
    @empty
    <div class="card card-pad text-sub" style="grid-column:1/-1; text-align:center;">No roles yet — add one above.</div>
    @endforelse
</div>

@if ($selectedRole)
<div class="card">
    <div class="card-head">
        <h3>Permission Matrix — {{ $selectedRole->name }}</h3>
        <div class="card-head__sub">Toggle module-level permissions, then Save</div>
        @if ($roles->count() > 1)
        <form action="{{ route('admin.system.roles.destroy', $selectedRole->id) }}" method="POST" onsubmit="return confirm('Delete the {{ $selectedRole->name }} role?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-ghost btn-sm"><i data-lucide="trash-2"></i> Delete Role</button>
        </form>
        @endif
    </div>

    <form action="{{ route('admin.system.roles.permissions.update', $selectedRole->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Module</th>
                    @foreach($actions as $a)<th style="text-align:center;">{{ $a }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
            @foreach($modules as $m)
            <tr>
                <td><b>{{ $m }}</b></td>
                @foreach($actions as $a)
                    @php $on = $selectedRole->can($m, $a); @endphp
                    <td style="text-align:center;">
                        <label style="cursor:pointer; display:inline-block;">
                            <input type="checkbox" name="permissions[]" value="{{ $m }}|{{ $a }}" class="switch-checkbox" {{ $on ? 'checked' : '' }}>
                            <span class="switch" style="margin:0 auto;"><i></i></span>
                        </label>
                    </td>
                @endforeach
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <div style="padding:14px 20px;">
            <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> Save Permissions</button>
        </div>
    </form>
</div>

<p class="text-sub" style="font-size:12px; margin-top:12px;">
    Note: this matrix defines what each role is <em>allowed</em> to do, but nothing in the app
    checks it yet — that's a separate step (adding a permission check to each controller
    action). Right now this page lets you plan and record the permission structure.
</p>
@endif
@endsection
