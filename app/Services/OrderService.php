<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\DeliveryLocationUpdated;
use App\Events\NewOrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Models\ActivityLog;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderNumberGenerator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class OrderService
{
    /** Order status graph: which transitions each direction allows. Enforced server-side, not just in the UI. */
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'rejected', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['ready_for_pickup', 'assigned', 'cancelled'],
        'ready_for_pickup' => ['completed', 'cancelled'],
        'assigned' => ['out_for_delivery', 'cancelled'],
        'out_for_delivery' => ['delivered', 'cancelled'],
        'delivered' => ['completed'],
        'completed' => [],
        'cancelled' => [],
        'rejected' => [],
    ];

    public function __construct(
        private readonly InventoryService $inventory,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Places a new order from the customer's cart.
     *
     * @param  array<int, array{product_id:int, quantity:int}>  $lines
     */
    public function place(User $customer, array $lines, string $deliveryMethod, ?int $addressId, string $phone, string $scheduleDate, ?string $slot, string $paymentMethod, ?string $notes, string $checkoutToken): Order
    {
        if (empty($lines)) {
            throw new InvalidArgumentException('Your cart is empty.');
        }

        return DB::transaction(function () use ($customer, $lines, $deliveryMethod, $addressId, $phone, $scheduleDate, $slot, $paymentMethod, $notes, $checkoutToken) {
            // Idempotency: a resubmitted form (double click, refresh) with the same token
            // returns the already-created order instead of billing twice.
            if ($existing = Order::where('checkout_token', $checkoutToken)->first()) {
                return $existing;
            }

            $address = null;
            $deliveryFee = 0;
            $addressText = null;

            if ($deliveryMethod === 'delivery') {
                $address = Address::where('user_id', $customer->id)->findOrFail($addressId);
                $deliveryFee = (float) ($address->deliveryArea?->fee ?? 0);
                $addressText = $address->fullText();
            }

            $subtotal = 0;
            $items = [];

            foreach ($lines as $line) {
                $product = Product::active()->lockForUpdate()->findOrFail($line['product_id']);
                $qty = max(1, (int) $line['quantity']);

                if (($product->inventory?->quantity_on_hand ?? 0) < $qty) {
                    throw new InvalidArgumentException("{$product->name} does not have enough stock right now.");
                }

                $lineSubtotal = $product->price * $qty;
                $subtotal += $lineSubtotal;

                $items[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'subtotal' => $lineSubtotal,
                ];
            }

            $order = Order::create([
                'order_number' => OrderNumberGenerator::next(),
                'checkout_token' => $checkoutToken,
                'customer_id' => $customer->id,
                'delivery_method' => $deliveryMethod,
                'address_id' => $address?->id,
                'delivery_address' => $addressText,
                'delivery_lat' => $address?->lat,
                'delivery_lng' => $address?->lng,
                'contact_phone' => $phone,
                'schedule_date' => $scheduleDate,
                'schedule_slot' => $slot,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total_amount' => $subtotal + $deliveryFee,
                'payment_method' => $paymentMethod,
                'payment_status' => 'unpaid',
                'status' => OrderStatus::Pending,
                'customer_notes' => $notes,
            ]);

            foreach ($items as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            $this->logTransition($order, null, OrderStatus::Pending, $customer->id, 'Order placed by customer');
            ActivityLog::record('orders', 'placed', $order->id, $order->order_number, $customer->id);

            event(new NewOrderPlaced($order));
            DB::afterCommit(fn () => $this->notifications->sendToMany(
                User::role(['admin', 'staff'])->get(),
                'New order received',
                'Order '.$order->order_number.' is ready for review.',
                'info',
                route('manage.orders.show', $order),
            ));

            return $order;
        });
    }

    /** Staff/admin moves an order forward or rejects/cancels it. Validates the transition and keeps stock in sync. */
    public function transition(Order $order, OrderStatus $to, User $actor, ?string $note = null): Order
    {
        $allowed = self::TRANSITIONS[$order->status->value] ?? [];

        if (! in_array($to->value, $allowed, true)) {
            throw new RuntimeException("Cannot move order from {$order->status->label()} to {$to->label()}.");
        }

        if ($to === OrderStatus::Preparing && $order->payment_method === 'gcash' && ! $order->isPaid()) {
            throw new RuntimeException('This order is set to pay via GCash and the customer has not completed payment yet.');
        }

        return DB::transaction(function () use ($order, $to, $actor, $note) {
            $from = $order->status;
            $order->update(['status' => $to]);

            if ($to === OrderStatus::Preparing && ! $order->stock_deducted) {
                $this->inventory->deductForOrder($order, $actor->id);
            }

            if (in_array($to, [OrderStatus::Cancelled, OrderStatus::Rejected], true)) {
                $order->update(['cancel_reason' => $note]);

                if ($order->stock_deducted) {
                    $this->inventory->restoreForOrder($order, $actor->id);
                }

                // Money was already collected online — there's no live refund
                // gateway here, so this just flags it for an admin to process
                // manually (in the actual GCash account) and mark done.
                if ($order->isPaid() && $order->payment) {
                    $order->payment->update(['refund_status' => 'pending']);
                }
            }

            if ($to === OrderStatus::Delivered || ($to === OrderStatus::Completed && $order->payment_method === 'cash')) {
                $order->update(['payment_status' => 'paid', 'paid_at' => $order->paid_at ?? now()]);
            }

            $this->logTransition($order, $from, $to, $actor->id, $note);
            ActivityLog::record('orders', 'status_changed', $order->id, "{$from->label()} → {$to->label()}", $actor->id);

            event(new OrderStatusUpdated($order->fresh(), $from->value));

            // SMS only for the moments that actually warrant a text — every other
            // status change still gets the in-app notification above, just no SMS.
            $smsMessage = match ($to) {
                OrderStatus::Confirmed => "Your order {$order->order_number} has been confirmed and is being prepared.",
                OrderStatus::OutForDelivery => "Your order {$order->order_number} is out for delivery!",
                OrderStatus::Delivered => "Your order {$order->order_number} has been delivered. Thank you for choosing us!",
                OrderStatus::Cancelled, OrderStatus::Rejected => "Your order {$order->order_number} was cancelled."
                    .($note ? " Reason: {$note}" : '')
                    .($order->isPaid() ? ' Your payment will be refunded — we\'ll be in touch.' : ''),
                default => null,
            };

            $this->notifications->send(
                $order->customer,
                'Order '.$order->order_number,
                'Your order is now: '.$to->label().'.',
                $to->tone(),
                route('customer.orders.show', $order),
                $smsMessage
            );

            return $order->fresh();
        });
    }

    /** Assigns a rider and moves the order into the delivery flow. */
    public function assignRider(Order $order, User $rider, User $actor): Order
    {
        if ($order->status !== OrderStatus::Preparing && $order->status !== OrderStatus::Confirmed) {
            throw new RuntimeException('Only confirmed or preparing orders can be assigned to a rider.');
        }

        return DB::transaction(function () use ($order, $rider, $actor) {
            $order->delivery()->updateOrCreate([], [
                'rider_id' => $rider->id,
                'address_id' => $order->address_id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            $order = $this->transition($order, OrderStatus::Assigned, $actor, 'Assigned to '.$rider->name);

            $this->notifications->send(
                $rider,
                'New delivery assigned',
                'Order '.$order->order_number.' has been assigned to you.',
                'info',
                route('rider.deliveries.show', $order),
                "New delivery assigned: order {$order->order_number}. Check the app for details."
            );

            return $order;
        });
    }

    /** Called from the rider's browser (geolocation watchPosition) while an order is out for delivery. */
    public function reportRiderLocation(Order $order, float $lat, float $lng): void
    {
        $order->delivery()->update([
            'last_lat' => $lat,
            'last_lng' => $lng,
            'location_updated_at' => now(),
        ]);

        event(new DeliveryLocationUpdated($order, $lat, $lng));
    }

    /** @return array<int, string> the statuses this order may legally move to next */
    public function availableTransitions(Order $order): array
    {
        return self::TRANSITIONS[$order->status->value] ?? [];
    }

    private function logTransition(Order $order, ?OrderStatus $from, OrderStatus $to, int $userId, ?string $note): void
    {
        $order->histories()->create([
            'user_id' => $userId,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'note' => $note,
        ]);
    }
}
