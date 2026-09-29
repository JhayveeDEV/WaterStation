<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever an order's status changes. Broadcast immediately (not queued)
 * so the customer's tracker and the staff board update live with no delay.
 */
class OrderStatusUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order, public ?string $fromStatus = null) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.'.$this->order->customer_id),
            new PrivateChannel('staff'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'status' => $this->order->status->value,
            'status_label' => $this->order->status->label(),
            'tone' => $this->order->status->tone(),
            'from_status' => $this->fromStatus,
            'updated_at' => $this->order->updated_at?->toIso8601String(),
        ];
    }
}
