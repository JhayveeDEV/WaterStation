<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_renders_without_an_existing_user(): void
    {
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
        $admin = User::where('email', 'admin@waterstation.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Create user');
    }

    public function test_valid_user_form_creates_user_with_selected_role(): void
    {
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
        $admin = User::where('email', 'admin@waterstation.test')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Staff Member',
            'email' => 'new-staff@example.com',
            'phone' => '09170000000',
            'role' => 'staff',
            'status' => 'active',
            'password' => 'StrongPassword123!',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'new-staff@example.com')->firstOrFail();
        $this->assertSame('New Staff Member', $user->name);
        $this->assertSame('09170000000', $user->phone);
        $this->assertTrue(Hash::check('StrongPassword123!', $user->password));
        $this->assertTrue($user->hasRole('staff'));
    }

    public function test_user_can_be_edited_and_updated_without_changing_blank_password(): void
    {
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
        $admin = User::where('email', 'admin@waterstation.test')->firstOrFail();
        $user = User::where('email', 'staff@waterstation.test')->firstOrFail();
        $originalPassword = $user->password;

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee('Edit user');

        $response = $this->put(route('admin.users.update', $user), [
            'name' => 'Updated Staff Member',
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => 'rider',
            'status' => 'inactive',
            'password' => '',
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('Updated Staff Member', $user->name);
        $this->assertSame('inactive', $user->status);
        $this->assertSame($originalPassword, $user->password);
        $this->assertTrue($user->hasRole('rider'));
        $this->assertFalse($user->hasRole('staff'));
    }

    public function test_search_results_remain_within_the_selected_role(): void
    {
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
        $admin = User::where('email', 'admin@waterstation.test')->firstOrFail();
        $staff = User::where('email', 'staff@waterstation.test')->firstOrFail();
        $rider = User::where('email', 'rider@waterstation.test')->firstOrFail();

        $staff->update(['email' => 'needle@example.com']);
        $rider->update(['email' => 'other-rider@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'rider', 'search' => 'needle']))
            ->assertOk()
            ->assertDontSee('needle@example.com')
            ->assertSee('No users match your search.');
    }

    public function test_status_toggle_changes_other_users_and_prevents_self_deactivation(): void
    {
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
        $admin = User::where('email', 'admin@waterstation.test')->firstOrFail();
        $staff = User::where('email', 'staff@waterstation.test')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle-status', $admin))
            ->assertStatus(422);

        $this->assertSame('active', $admin->fresh()->status);

        $this->patch(route('admin.users.toggle-status', $staff))
            ->assertRedirect();

        $this->assertSame('inactive', $staff->fresh()->status);
    }
}
