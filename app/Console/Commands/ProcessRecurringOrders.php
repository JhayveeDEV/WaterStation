<?php

namespace App\Console\Commands;

use App\Models\RecurringOrder;
use App\Services\NotificationService;
use App\Services\OrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class ProcessRecurringOrders extends Command
{
    protected $signature = 'recurring-orders:process';

    protected $description = 'Create customer orders whose repeat schedule is due';

    public function handle(OrderService $orders, NotificationService $notifications): int
    {
        RecurringOrder::where('is_active', true)
            ->whereDate('next_order_date', '<=', today())
            ->orderBy('id')
            ->chunkById(50, function ($recurringOrders) use ($orders, $notifications) {
                foreach ($recurringOrders as $recurringOrder) {
                    $dueDate = $recurringOrder->next_order_date->toDateString();

                    try {
                        $order = $orders->place(
                            customer: $recurringOrder->customer,
                            lines: $recurringOrder->items,
                            deliveryMethod: $recurringOrder->delivery_method,
                            addressId: $recurringOrder->address_id,
                            phone: $recurringOrder->contact_phone,
                            scheduleDate: $dueDate,
                            slot: $recurringOrder->schedule_slot,
                            paymentMethod: 'cash',
                            notes: $recurringOrder->customer_notes,
                            checkoutToken: hash('sha256', 'recurring-order:'.$recurringOrder->id.':'.$dueDate),
                        );

                        $recurringOrder->update([
                            'last_order_id' => $order->id,
                            'next_order_date' => $recurringOrder->nextDateAfter(today()),
                            'last_error' => null,
                        ]);

                        $notifications->send(
                            $recurringOrder->customer,
                            'Repeat order placed',
                            'Your scheduled order '.$order->order_number.' was placed. Payment is due in cash.',
                            'ok',
                            route('customer.orders.show', $order),
                        );

                        $this->info('Created '.$order->order_number.' from repeat schedule '.$recurringOrder->id.'.');
                    } catch (Throwable $exception) {
                        $recurringOrder->update([
                            'next_order_date' => today()->addDay(),
                            'last_error' => Str::limit($exception->getMessage(), 1000),
                        ]);

                        $notifications->send(
                            $recurringOrder->customer,
                            'Repeat order needs attention',
                            'We could not place your scheduled order. Check product availability and your saved delivery address, then try again tomorrow.',
                            'warning',
                            route('customer.recurring.index'),
                        );

                        report($exception);
                        $this->warn('Could not process repeat schedule '.$recurringOrder->id.'.');
                    }
                }
            });

        return self::SUCCESS;
    }
}
