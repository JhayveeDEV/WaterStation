<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case ReadyForPickup = 'ready_for_pickup';
    case Assigned = 'assigned';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Preparing => 'Preparing',
            self::ReadyForPickup => 'Ready for pickup',
            self::Assigned => 'Rider assigned',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
            self::Rejected => 'Rejected',
        };
    }

    /** Used by the badge component to pick a small status dot colour. */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Confirmed, self::Preparing, self::ReadyForPickup, self::Assigned, self::OutForDelivery => 'info',
            self::Delivered, self::Completed => 'ok',
            self::Cancelled, self::Rejected => 'bad',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Rejected], true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
