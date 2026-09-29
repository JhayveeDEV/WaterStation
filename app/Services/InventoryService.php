<?php

namespace App\Services;

use App\Events\ProductStockUpdated;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /** Stock received from a supplier. */
    public function receive(Product $product, int $quantity, ?string $reason, int $userId, ?string $supplierName = null, ?float $unitCost = null): InventoryMovement
    {
        return $this->applyMovement($product, abs($quantity), 'received', $reason, $userId, $supplierName, $unitCost);
    }

    /** Manual correction; quantity may be positive or negative. */
    public function adjust(Product $product, int $quantity, ?string $reason, int $userId): InventoryMovement
    {
        if ($quantity === 0) {
            throw new InvalidArgumentException('Adjustment quantity cannot be zero.');
        }

        return $this->applyMovement($product, $quantity, 'adjusted', $reason, $userId);
    }

    public function markDamaged(Product $product, int $quantity, ?string $reason, int $userId): InventoryMovement
    {
        return $this->applyMovement($product, -abs($quantity), 'damaged', $reason, $userId);
    }

    /** Deducts stock for a confirmed order. Locks the row so concurrent checkouts can't oversell. */
    public function deductForOrder(Order $order, int $userId): void
    {
        $balances = DB::transaction(function () use ($order, $userId) {
            $order->loadMissing('items.product.inventory');
            $balances = [];

            foreach ($order->items as $item) {
                $inventory = $item->product->inventory()->lockForUpdate()->first();

                if (! $inventory || $inventory->quantity_on_hand < $item->quantity) {
                    throw new InvalidArgumentException("Insufficient stock for {$item->product_name}.");
                }

                $previousBalance = $balances[$item->product_id]['previous'] ?? $inventory->quantity_on_hand;
                $inventory->decrement('quantity_on_hand', $item->quantity);
                $newBalance = $inventory->fresh()->quantity_on_hand;

                InventoryMovement::create([
                    'product_id' => $item->product_id,
                    'user_id' => $userId,
                    'order_id' => $order->id,
                    'type' => 'used',
                    'quantity' => -$item->quantity,
                    'balance_after' => $newBalance,
                    'reason' => 'Order '.$order->order_number,
                ]);

                $balances[$item->product_id] = [
                    'product' => $item->product,
                    'previous' => $previousBalance,
                    'balance' => $newBalance,
                ];
            }

            $order->update(['stock_deducted' => true]);

            return $balances;
        });

        $this->broadcastBalances($balances);

        foreach ($balances as $row) {
            $this->notifyIfLow($row['product'], $row['previous'], $row['balance']);
        }
    }

    /** Returns stock to inventory if a paid/prepared order gets cancelled after stock left the shelf. */
    public function restoreForOrder(Order $order, int $userId): void
    {
        if (! $order->stock_deducted) {
            return;
        }

        $balances = DB::transaction(function () use ($order, $userId) {
            $order->loadMissing('items');
            $balances = [];

            foreach ($order->items as $item) {
                $inventory = $item->product->inventory()->lockForUpdate()->first();
                $inventory?->increment('quantity_on_hand', $item->quantity);
                $newBalance = $inventory?->fresh()->quantity_on_hand ?? 0;

                InventoryMovement::create([
                    'product_id' => $item->product_id,
                    'user_id' => $userId,
                    'order_id' => $order->id,
                    'type' => 'returned',
                    'quantity' => $item->quantity,
                    'balance_after' => $newBalance,
                    'reason' => 'Cancelled order '.$order->order_number,
                ]);

                $balances[$item->product_id] = ['product' => $item->product, 'balance' => $newBalance];
            }

            $order->update(['stock_deducted' => false]);

            return $balances;
        });

        $this->broadcastBalances($balances);
    }

    private function applyMovement(Product $product, int $quantity, string $type, ?string $reason, int $userId, ?string $supplierName = null, ?float $unitCost = null): InventoryMovement
    {
        $movement = DB::transaction(function () use ($product, $quantity, $type, $reason, $userId, $supplierName, $unitCost) {
            $inventory = $product->inventory()->lockForUpdate()->first()
                ?? $product->inventory()->create(['quantity_on_hand' => 0, 'reorder_level' => 10]);

            $newBalance = $inventory->quantity_on_hand + $quantity;

            if ($newBalance < 0) {
                throw new InvalidArgumentException('This would make stock negative.');
            }

            $inventory->update(['quantity_on_hand' => $newBalance]);

            return InventoryMovement::create([
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => $type,
                'quantity' => $quantity,
                'balance_after' => $newBalance,
                'reason' => $reason,
                'supplier_name' => $supplierName,
                'unit_cost' => $unitCost,
            ]);
        });

        // Fired after the transaction commits, never from inside it — a broadcast
        // for a change that then rolls back would show customers stock that isn't real.
        event(new ProductStockUpdated($product, $movement->balance_after));
        $this->notifyIfLow($product, $movement->balance_after - $movement->quantity, $movement->balance_after);

        return $movement;
    }

    /** @param  array<int, array{product: Product, balance:int, previous?:int}>  $balances */
    private function broadcastBalances(array $balances): void
    {
        foreach ($balances as $row) {
            event(new ProductStockUpdated($row['product'], $row['balance']));
        }
    }

    private function notifyIfLow(Product $product, int $previousBalance, int $balance): void
    {
        $reorderLevel = $product->inventory()->value('reorder_level');

        if ($reorderLevel === null || $previousBalance <= $reorderLevel || $balance > $reorderLevel) {
            return;
        }

        $this->notifications->sendToMany(
            User::role(['admin', 'staff'])->get(),
            'Low stock: '.$product->name,
            'Only '.$balance.' '.$product->unit.' remaining. Reorder level: '.$reorderLevel.'.',
            'warning',
            route('manage.inventory.movements', $product),
        );
    }
}
