<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast on a public channel (no auth needed) so every customer currently
 * browsing the shop sees stock levels change live — e.g. an item selling out
 * while they have it in their cart, or staff receiving new stock.
 */
class ProductStockUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public Product $product, public int $quantityOnHand) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new Channel('inventory')];
    }

    public function broadcastAs(): string
    {
        return 'stock.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->product->id,
            'quantity_on_hand' => $this->quantityOnHand,
            'in_stock' => $this->quantityOnHand > 0,
        ];
    }
}
