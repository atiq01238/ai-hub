<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\RolePermission;
use App\Support\PermissionMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::withCount('users')->orderBy('name')->get();

        $selectedRole = $request->query('role_id')
            ? Role::with(['permissions', 'users' => fn ($q) => $q->orderBy('name')])->find($request->query('role_id'))
            : $roles->first()?->load(['permissions', 'users' => fn ($q) => $q->orderBy('name')]);

        return view('system.roles', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'modules' => PermissionMatrix::modules(),
            'actions' => PermissionMatrix::actions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $data['slug'] = $this->uniqueSlug($data['name']);

        $role = DB::transaction(function () use ($data) {
            $role = Role::create($data);
            $this->syncPermissionRows($role, []);
            return $role;
        });

        $this->log(request(), 'role_created', $role, "Created role {$role->name}");

        return redirect()->route('admin.system.roles', ['role_id' => $role->id])
            ->with('status', 'Role created. All permissions start disabled for least-privilege safety.');
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);
        abort_if($role->isSystemRole(), 422, 'Super Admin is a protected system role and cannot be renamed.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $role->update([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name'], $role->id),
            'color' => $data['color'],
        ]);

        $this->log($request, 'role_updated', $role, "Updated role {$role->name}");

        return back()->with('status', 'Role details updated.');
    }

    public function updatePermissions(Request $request, int $roleId)
    {
        $role = Role::findOrFail($roleId);
        abort_if($role->isSystemRole(), 422, 'Super Admin always has full access; its permissions cannot be restricted.');

        $checked = array_values(array_filter($request->input('permissions', []), 'is_string'));

        DB::transaction(fn () => $this->syncPermissionRows($role, $checked));
        $role->unsetRelation('permissions');

        $this->log($request, 'role_permissions_updated', $role, "Updated permissions for {$role->name}");

        return redirect()->route('admin.system.roles', ['role_id' => $role->id])
            ->with('status', 'Permissions saved. Sidebar visibility and protected actions now follow this role.');
    }

    public function destroy(Request $request, int $id)
    {
        $role = Role::withCount('users')->findOrFail($id);
        abort_if($role->isSystemRole(), 422, 'Super Admin is a protected system role and cannot be deleted.');
        abort_if($role->users_count > 0, 422, 'Reassign users before deleting this role.');

        $name = $role->name;
        $role->delete();
        $this->log($request, 'role_deleted', null, "Deleted role {$name}");

        return redirect()->route('admin.system.roles')->with('status', 'Role deleted.');
    }

    private function syncPermissionRows(Role $role, array $checked): void
    {
        foreach (PermissionMatrix::modules() as $module => $supportedActions) {
            foreach (PermissionMatrix::actions() as $action) {
                if (! in_array($action, $supportedActions, true)) {
                    RolePermission::where([
                        'role_id' => $role->id,
                        'module' => $module,
                        'action' => $action,
                    ])->delete();
                    continue;
                }

                RolePermission::updateOrCreate(
                    ['role_id' => $role->id, 'module' => $module, 'action' => $action],
                    ['allowed' => in_array("{$module}|{$action}", $checked, true)]
                );
            }
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        $i = 2;

        while (Role::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    private function log(Request $request, string $action, ?Role $role, string $description): void
    {
        if (! $request->user()) {
            return;
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'subject_type' => Role::class,
            'subject_id' => $role?->id,
            'description' => $description,
        ]);
    }
}
