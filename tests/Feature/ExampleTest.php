<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_demo_accounts_can_log_in(): void
    {
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);

        $response = $this->post('/login', [
            'email' => 'admin@waterstation.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'admin@waterstation.test')->first());
    }

    public function test_user_without_role_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'norole@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
