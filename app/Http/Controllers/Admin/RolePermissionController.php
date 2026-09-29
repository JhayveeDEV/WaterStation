<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Lets the admin see and adjust exactly what each of the 4 roles can do,
 * on top of the fixed role assigned to each user in Admin\UserController.
 */
class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        // The admin role always keeps full access — the system depends on at
        // least one role being able to reach every screen, including this one.
        if ($role->name === 'admin') {
            $role->syncPermissions(Permission::all());

            return back()->with('status', 'The admin role always keeps every permission.');
        }

        $role->syncPermissions($data['permissions'] ?? []);

        ActivityLog::record('roles', 'permissions_updated', $role->id, $role->name.' → '.implode(', ', $data['permissions'] ?? ['none']));

        return back()->with('status', 'Permissions updated for '.ucfirst($role->name).'.');
    }
}
