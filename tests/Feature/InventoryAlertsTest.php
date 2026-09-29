<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InventoryAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_restock_tracks_supplier_cost_and_low_stock_alerts_only_on_threshold_crossing(): void
    {
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
        Event::fake();

        $staff = User::where('email', 'staff@waterstation.test')->firstOrFail();
        $product = Product::factory()->withStock(12)->create();
        $inventory = app(InventoryService::class);

        $receipt = $inventory->receive($product, 5, 'Weekly delivery', $staff->id, 'Clear Spring Supplier', 18.50);

        $this->assertSame('Clear Spring Supplier', $receipt->supplier_name);
        $this->assertSame('18.50', $receipt->fresh()->unit_cost);

        $inventory->adjust($product, -7, 'Customer orders', $staff->id);
        $this->assertDatabaseCount('notifications', 2);

        $inventory->adjust($product, -1, 'More orders', $staff->id);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertInstanceOf(InventoryMovement::class, $receipt);
    }
}
