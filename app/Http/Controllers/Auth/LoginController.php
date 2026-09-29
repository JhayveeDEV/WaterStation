<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureDemoAccounts();

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        $user = Auth::user();

        if (! $user->isActive()) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'This account has been deactivated. Please contact the admin.']);
        }

        if (! $user->hasAnyRole(['customer', 'staff', 'admin', 'rider'])) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'This account is not assigned a role yet. Please contact an administrator.']);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->homeFor($user->getRoleNameAttribute()));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function ensureDemoAccounts(): void
    {
        $permissions = [
            'view shop', 'place orders', 'manage own orders', 'process orders',
            'manage inventory', 'assign riders', 'view deliveries', 'update deliveries',
            'manage users', 'manage settings', 'view reports',
        ];

        $newPermissions = [];
        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);

            if ($permission->wasRecentlyCreated) {
                $newPermissions[] = $permission;
            }
        }

        $roles = [
            'admin' => $permissions,
            'staff' => ['process orders', 'manage inventory', 'assign riders', 'view deliveries', 'view reports'],
            'rider' => ['view deliveries', 'update deliveries'],
            'customer' => ['view shop', 'place orders', 'manage own orders'],
        ];

        $adminRole = null;
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            if ($role->wasRecentlyCreated) {
                $role->syncPermissions($rolePermissions);
            }

            if ($roleName === 'admin') {
                $adminRole = $role;
            }
        }

        if ($newPermissions && $adminRole && ! $adminRole->wasRecentlyCreated) {
            $adminRole->givePermissionTo($newPermissions);
        }

        $accounts = [
            ['name' => 'Aira Santos', 'email' => 'admin@waterstation.test', 'role' => 'admin'],
            ['name' => 'Marco Reyes', 'email' => 'staff@waterstation.test', 'role' => 'staff'],
            ['name' => 'Ben delos Reyes', 'email' => 'rider@waterstation.test', 'role' => 'rider'],
            ['name' => 'Carla Cruz', 'email' => 'customer@waterstation.test', 'role' => 'customer'],
        ];

        foreach ($accounts as $account) {
            $user = User::firstOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'phone' => '09171234567',
                    'password' => bcrypt('password'),
                    'status' => 'active',
                ]
            );

            if (! $user->hasRole($account['role'])) {
                $user->assignRole($account['role']);
            }
        }
    }

    private function homeFor(string $role): string
    {
        return match ($role) {
            'admin' => route('admin.dashboard'),
            'staff' => route('manage.dashboard'),
            'rider' => route('rider.dashboard'),
            'customer' => route('customer.shop'),
            default => route('login'),
        };
    }
}
