<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\RecurringOrder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RecurringOrderCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_repeat_creates_a_cash_order_and_advances_its_schedule(): void
    {
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
        Event::fake();

        $customer = User::where('email', 'customer@waterstation.test')->firstOrFail();
        $product = Product::factory()->withStock(5)->create();
        $repeat = RecurringOrder::create([
            'customer_id' => $customer->id,
            'delivery_method' => 'pickup',
            'contact_phone' => $customer->phone,
            'frequency' => 'weekly',
            'next_order_date' => today()->toDateString(),
            'items' => [[
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => 2,
            ]],
            'is_active' => true,
        ]);

        $this->artisan('recurring-orders:process')->assertExitCode(0);

        $order = Order::sole();
        $this->assertSame('cash', $order->payment_method);
        $this->assertSame('pending', $order->status->value);
        $this->assertSame(2, $order->items()->sole()->quantity);
        $this->assertSame(today()->addWeek()->toDateString(), $repeat->fresh()->next_order_date->toDateString());
        $this->assertDatabaseHas('notifications', [
            'user_id' => $customer->id,
            'title' => 'Repeat order placed',
        ]);

        $this->artisan('recurring-orders:process')->assertExitCode(0);
        $this->assertDatabaseCount('orders', 1);
    }
}
