<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ProductionDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ProductionDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_baseline_data_and_configured_admin_without_demo_orders(): void
    {
        config([
            'services.production_admin.name' => 'Station Admin',
            'services.production_admin.email' => 'owner@example.com',
            'services.production_admin.password' => 'a-strong-test-password-123',
        ]);

        $this->seed(ProductionDatabaseSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('delivery_areas', ['name' => 'Within 2 km (Barangay proper)']);
        $this->assertDatabaseHas('products', ['name' => 'Round Gallon Refill']);
        $admin = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue(Hash::check('a-strong-test-password-123', $admin->password));
        $this->assertDatabaseMissing('users', ['email' => 'admin@waterstation.test']);
        $this->assertDatabaseMissing('orders', ['order_number' => 'WS-DEMO-1001']);
    }

    public function test_reseeding_does_not_reset_an_existing_admin_password(): void
    {
        config([
            'services.production_admin.email' => 'owner@example.com',
            'services.production_admin.password' => 'first-admin-password-123',
        ]);

        $this->seed(ProductionDatabaseSeeder::class);

        config(['services.production_admin.password' => 'replacement-admin-password-456']);
        $this->seed(ProductionDatabaseSeeder::class);

        $admin = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('first-admin-password-123', $admin->password));
    }

    public function test_it_requires_a_valid_admin_email_and_strong_password(): void
    {
        config([
            'services.production_admin.email' => 'invalid-email',
            'services.production_admin.password' => 'short',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Set a valid ADMIN_EMAIL and an ADMIN_PASSWORD');

        $this->seed(ProductionDatabaseSeeder::class);
    }

    public function test_it_does_not_promote_an_existing_non_admin_account(): void
    {
        config([
            'services.production_admin.email' => 'customer@example.com',
            'services.production_admin.password' => 'a-strong-test-password-123',
        ]);

        $customer = User::factory()->create(['email' => 'customer@example.com']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_EMAIL already belongs to a non-admin account');

        try {
            $this->seed(ProductionDatabaseSeeder::class);
        } finally {
            $this->assertFalse($customer->fresh()->hasRole('admin'));
        }
    }
}
