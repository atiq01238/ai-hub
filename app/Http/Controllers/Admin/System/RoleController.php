<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    // The full matrix grid — same modules/actions for every role.
    private array $modules = ['AI Tools', 'AI Models', 'AI News', 'Comparisons', 'Pricing', 'Users', 'Reviews', 'Settings', 'Security'];
    private array $actions = ['View', 'Add', 'Edit', 'Delete', 'Publish', 'Export'];

    public function index(Request $request)
    {
        $roles = Role::withCount('users')->orderBy('name')->get();

        $selectedRole = $request->query('role_id')
            ? Role::with('permissions')->find($request->query('role_id'))
            : $roles->first()?->load('permissions');

        return view('system.roles', [
            'roles'        => $roles,
            'selectedRole' => $selectedRole,
            'modules'      => $this->modules,
            'actions'      => $this->actions,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'max:7'],
        ]);
        $data['slug'] = Str::slug($data['name']);

        $role = Role::create($data);

        // Start every permission OFF — the admin turns on what this role
        // actually needs from the matrix below, nothing pre-assumed.
        foreach ($this->modules as $module) {
            foreach ($this->actions as $action) {
                RolePermission::create(['role_id' => $role->id, 'module' => $module, 'action' => $action, 'allowed' => false]);
            }
        }

        return redirect()->route('admin.system.roles', ['role_id' => $role->id])->with('status', 'Role created.');
    }

    public function updatePermissions(Request $request, int $roleId)
    {
        $role = Role::findOrFail($roleId);
        $checked = $request->input('permissions', []); // array of "Module|Action" strings that were checked

        foreach ($this->modules as $module) {
            foreach ($this->actions as $action) {
                RolePermission::updateOrCreate(
                    ['role_id' => $role->id, 'module' => $module, 'action' => $action],
                    ['allowed' => in_array("{$module}|{$action}", $checked)]
                );
            }
        }

        return redirect()->route('admin.system.roles', ['role_id' => $role->id])->with('status', 'Permissions saved.');
    }

    public function destroy(int $id)
    {
        Role::findOrFail($id)->delete();

        return redirect()->route('admin.system.roles')->with('status', 'Role deleted.');
    }
}
