<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_reseeding_preserve_custom_role_permissions(): void
    {
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);

        $staff = Role::findByName('staff');
        $staff->givePermissionTo(Permission::create(['name' => 'custom capability', 'guard_name' => 'web']));
        $staff->revokePermissionTo('manage inventory');

        $this->post('/login', [
            'email' => 'staff@waterstation.test',
            'password' => 'password',
        ])->assertRedirect(route('manage.dashboard'));

        $staff = $staff->fresh();
        $this->assertTrue($staff->hasPermissionTo('custom capability'));
        $this->assertFalse($staff->hasPermissionTo('manage inventory'));

        $this->seed(RolePermissionSeeder::class);

        $staff = $staff->fresh();
        $this->assertTrue($staff->hasPermissionTo('custom capability'));
        $this->assertFalse($staff->hasPermissionTo('manage inventory'));
        $this->assertAuthenticatedAs(User::where('email', 'staff@waterstation.test')->first());
    }
}
