<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Delivery;
use App\Models\DeliveryArea;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\RecurringOrder;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@waterstation.test')->firstOrFail();
        $staff = User::where('email', 'staff@waterstation.test')->firstOrFail();
        $rider = User::where('email', 'rider@waterstation.test')->firstOrFail();
        $area = DeliveryArea::where('is_active', true)->orderBy('id')->firstOrFail();
        $address = Address::where('user_id', $customer->id)->where('is_default', true)->firstOrFail();
        $product = Product::where('name', 'Round Gallon Refill')->firstOrFail();

        $completedOrder = $this->seedCompletedOrder($customer, $staff, $rider, $address, $product);
        $this->seedRecurringOrder($customer, $completedOrder);
        $this->seedLowStockExample($staff);
        $this->seedNotifications($customer, $completedOrder);
    }

    private function seedCompletedOrder(User $customer, User $staff, User $rider, Address $address, Product $product): Order
    {
        $quantity = 2;
        $subtotal = (float) $product->price * $quantity;
        $deliveryFee = (float) ($address->deliveryArea?->fee ?? 0);
        $completedAt = now()->subMinutes(15);

        $order = Order::firstOrCreate(
            ['order_number' => 'WS-DEMO-1007'],
            [
                'customer_id' => $customer->id,
                'delivery_method' => 'delivery',
                'address_id' => $address->id,
                'delivery_address' => $address->fullText(),
                'delivery_lat' => $address->lat,
                'delivery_lng' => $address->lng,
                'contact_phone' => $customer->phone ?? '09171234567',
                'schedule_date' => today()->toDateString(),
                'schedule_slot' => '09:00-10:00',
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $subtotal + $deliveryFee,
                'payment_method' => 'cash',
                'payment_status' => 'paid',
                'paid_at' => $completedAt,
                'status' => OrderStatus::Completed,
                'customer_notes' => 'Demo completed delivery.',
                'stock_deducted' => true,
                'created_at' => now()->subHours(4),
                'updated_at' => $completedAt,
            ]
        );

        if (! $order->items()->exists()) {
            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'subtotal' => $subtotal,
            ]);
        }

        $steps = [
            [null, OrderStatus::Pending, $customer, 240],
            [OrderStatus::Pending, OrderStatus::Confirmed, $staff, 210],
            [OrderStatus::Confirmed, OrderStatus::Preparing, $staff, 180],
            [OrderStatus::Preparing, OrderStatus::Assigned, $staff, 60],
            [OrderStatus::Assigned, OrderStatus::OutForDelivery, $rider, 45],
            [OrderStatus::OutForDelivery, OrderStatus::Delivered, $rider, 20],
            [OrderStatus::Delivered, OrderStatus::Completed, $staff, 15],
        ];

        foreach ($steps as [$from, $to, $actor, $minutesAgo]) {
            OrderStatusHistory::firstOrCreate(
                ['order_id' => $order->id, 'to_status' => $to->value],
                [
                    'user_id' => $actor->id,
                    'from_status' => $from?->value,
                    'note' => 'Seeded demo order history.',
                    'created_at' => now()->subMinutes($minutesAgo),
                    'updated_at' => now()->subMinutes($minutesAgo),
                ]
            );
        }

        Delivery::firstOrCreate(
            ['order_id' => $order->id],
            [
                'rider_id' => $rider->id,
                'address_id' => $address->id,
                'status' => 'delivered',
                'assigned_at' => now()->subMinutes(60),
                'dispatched_at' => now()->subMinutes(45),
                'delivered_at' => now()->subMinutes(20),
                'notes' => 'Seeded completed demo delivery.',
            ]
        );

        InventoryMovement::firstOrCreate(
            ['order_id' => $order->id, 'type' => 'used'],
            [
                'product_id' => $product->id,
                'user_id' => $staff->id,
                'quantity' => -$quantity,
                'balance_after' => $product->inventory?->quantity_on_hand ?? 0,
                'reason' => 'Demo completed order '.$order->order_number,
            ]
        );

        return $order;
    }

    private function seedRecurringOrder(User $customer, Order $sourceOrder): void
    {
        $sourceOrder->loadMissing('items');

        RecurringOrder::firstOrCreate(
            ['source_order_id' => $sourceOrder->id],
            [
                'customer_id' => $customer->id,
                'delivery_method' => $sourceOrder->delivery_method,
                'address_id' => $sourceOrder->address_id,
                'contact_phone' => $sourceOrder->contact_phone,
                'schedule_slot' => $sourceOrder->schedule_slot,
                'frequency' => 'biweekly',
                'next_order_date' => today()->addWeeks(2)->toDateString(),
                'items' => $sourceOrder->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                ])->all(),
                'customer_notes' => 'Seeded demo repeat order.',
                'is_active' => true,
            ]
        );
    }

    private function seedLowStockExample(User $staff): void
    {
        $product = Product::where('name', 'New Slim Gallon (with water)')->firstOrFail();
        $inventory = $product->inventory()->firstOrFail();

        if (! $product->movements()->where('reason', 'Demo supplier receipt')->exists()) {
            $inventory->update([
                'reorder_level' => max($inventory->reorder_level, $inventory->quantity_on_hand + 5),
            ]);

            InventoryMovement::create([
                'product_id' => $product->id,
                'user_id' => $staff->id,
                'type' => 'received',
                'quantity' => 10,
                'balance_after' => $inventory->quantity_on_hand,
                'reason' => 'Demo supplier receipt',
                'supplier_name' => 'Clear Spring Wholesale',
                'unit_cost' => 18.50,
            ]);
        }
    }

    private function seedNotifications(User $customer, Order $order): void
    {
        $lowStockProduct = Product::where('name', 'New Slim Gallon (with water)')->firstOrFail();
        $inventory = $lowStockProduct->inventory()->firstOrFail();

        foreach (User::role(['admin', 'staff'])->get() as $user) {
            $user->appNotifications()->firstOrCreate(
                [
                    'title' => 'Low stock: '.$lowStockProduct->name,
                    'message' => 'Only '.$inventory->quantity_on_hand.' gallons remain. Reorder level: '.$inventory->reorder_level.'.',
                ],
                [
                    'type' => 'warning',
                    'url' => route('manage.inventory.movements', $lowStockProduct),
                    'is_read' => false,
                ]
            );

            $user->appNotifications()->firstOrCreate(
                [
                    'title' => 'New order received',
                    'message' => 'Order '.$order->order_number.' is ready for review.',
                ],
                [
                    'type' => 'info',
                    'url' => route('manage.orders.show', $order),
                    'is_read' => false,
                ]
            );
        }

        $customer->appNotifications()->firstOrCreate(
            [
                'title' => 'Repeat order scheduled',
                'message' => 'A biweekly repeat order is scheduled for '.today()->addWeeks(2)->format('M j, Y').'.',
            ],
            [
                'type' => 'info',
                'url' => route('customer.recurring.index'),
                'is_read' => false,
            ]
        );

        $customer->appNotifications()->firstOrCreate(
            [
                'title' => 'Order completed',
                'message' => 'Your order '.$order->order_number.' has been delivered.',
            ],
            [
                'type' => 'ok',
                'url' => route('customer.orders.show', $order),
                'is_read' => false,
            ]
        );
    }
}
