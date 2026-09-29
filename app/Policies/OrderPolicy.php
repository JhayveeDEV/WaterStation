<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /** Customers may only ever see their own orders. Staff/admin/rider access is handled by route role/permission gates. */
    public function view(User $user, Order $order): bool
    {
        if ($user->hasRole('customer')) {
            return $order->customer_id === $user->id;
        }

        if ($user->hasRole('rider')) {
            return $order->delivery?->rider_id === $user->id;
        }

        return $user->hasAnyRole(['staff', 'admin']);
    }

    /**
     * An order can only be self-cancelled by its customer while it's still pending.
     * Once staff have confirmed it — or it's been paid online — cancellation needs
     * staff to step in, so a paid order is never silently voided from the customer side.
     */
    public function cancel(User $user, Order $order): bool
    {
        return $order->customer_id === $user->id
            && $order->status->value === 'pending'
            && ! $order->isPaid();
    }

    /** A rider may act on a delivery only once it's actually assigned to them. */
    public function handleDelivery(User $user, Order $order): bool
    {
        return $order->delivery?->rider_id === $user->id;
    }
}
