<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Delivery;
use App\Models\DeliveryArea;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class MockOrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@waterstation.test')->firstOrFail();
        $rider = User::where('email', 'rider@waterstation.test')->firstOrFail();
        $area = DeliveryArea::where('is_active', true)->orderBy('id')->firstOrFail();
        $products = Product::where('is_active', true)->orderBy('id')->take(2)->get();

        $address = Address::firstOrCreate(
            ['user_id' => $customer->id, 'label' => 'Home'],
            [
                'delivery_area_id' => $area->id,
                'address_text' => 'Purok 3, Barangay Riverside',
                'city' => 'Davao City',
                'lat' => 7.0731,
                'lng' => 125.6128,
                'is_default' => true,
            ]
        );

        $orders = [
            ['number' => 'WS-DEMO-1001', 'status' => OrderStatus::Pending, 'method' => 'delivery'],
            ['number' => 'WS-DEMO-1002', 'status' => OrderStatus::Confirmed, 'method' => 'delivery'],
            ['number' => 'WS-DEMO-1003', 'status' => OrderStatus::Preparing, 'method' => 'delivery'],
            ['number' => 'WS-DEMO-1004', 'status' => OrderStatus::ReadyForPickup, 'method' => 'pickup'],
            ['number' => 'WS-DEMO-1005', 'status' => OrderStatus::Assigned, 'method' => 'delivery'],
            ['number' => 'WS-DEMO-1006', 'status' => OrderStatus::OutForDelivery, 'method' => 'delivery'],
        ];

        foreach ($orders as $index => $data) {
            $subtotal = 30 * ($index + 1);
            $fee = $data['method'] === 'delivery' ? (float) $area->fee : 0;
            $order = Order::firstOrCreate(
                ['order_number' => $data['number']],
                [
                    'customer_id' => $customer->id,
                    'delivery_method' => $data['method'],
                    'address_id' => $data['method'] === 'delivery' ? $address->id : null,
                    'delivery_address' => $data['method'] === 'delivery' ? $address->fullText() : null,
                    'delivery_lat' => $data['method'] === 'delivery' ? $address->lat : null,
                    'delivery_lng' => $data['method'] === 'delivery' ? $address->lng : null,
                    'contact_phone' => $customer->phone ?? '09171234567',
                    'schedule_date' => now()->addDays($index % 3)->toDateString(),
                    'schedule_slot' => '09:00-10:00',
                    'subtotal' => $subtotal,
                    'delivery_fee' => $fee,
                    'total_amount' => $subtotal + $fee,
                    'payment_method' => 'cash',
                    'payment_status' => 'unpaid',
                    'status' => $data['status'],
                    'customer_notes' => 'Demo order for the operations board.',
                ]
            );

            if ($order->wasRecentlyCreated) {
                $product = $products->first();
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $index + 1,
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ]);
            }

            if (in_array($data['status'], [OrderStatus::Assigned, OrderStatus::OutForDelivery], true)) {
                Delivery::firstOrCreate(
                    ['order_id' => $order->id],
                    [
                        'rider_id' => $rider->id,
                        'address_id' => $address->id,
                        'status' => $data['status'] === OrderStatus::OutForDelivery ? 'out_for_delivery' : 'assigned',
                        'assigned_at' => now(),
                        'dispatched_at' => $data['status'] === OrderStatus::OutForDelivery ? now() : null,
                    ]
                );
            }
        }
    }
}