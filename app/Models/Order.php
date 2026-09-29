<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'checkout_token', 'customer_id', 'delivery_method', 'address_id', 'delivery_address',
        'delivery_lat', 'delivery_lng',
        'contact_phone', 'schedule_date', 'schedule_slot', 'subtotal', 'delivery_fee', 'total_amount',
        'payment_method', 'payment_status', 'paid_at', 'status', 'customer_notes', 'cancel_reason', 'stock_deducted',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'schedule_date' => 'date',
            'paid_at' => 'datetime',
            'stock_deducted' => 'boolean',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'delivery_lat' => 'float',
            'delivery_lng' => 'float',
        ];
    }

    public function hasLocationPin(): bool
    {
        return $this->delivery_lat !== null && $this->delivery_lng !== null;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /** True while this order is set to pay online but hasn't paid yet — used to show the "Pay now" prompt. */
    public function isAwaitingOnlinePayment(): bool
    {
        return $this->payment_method === 'gcash'
            && ! $this->isPaid()
            && ! in_array($this->status->value, ['cancelled', 'rejected'], true);
    }

    public function isDelivery(): bool
    {
        return $this->delivery_method === 'delivery';
    }

    public function isPickup(): bool
    {
        return $this->delivery_method === 'pickup';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function getMethodLabelAttribute(): string
    {
        return $this->isDelivery() ? 'Delivery' : 'Pickup';
    }

    public function getScheduleLabelAttribute(): string
    {
        $date = $this->schedule_date->format('M j, Y');

        return $this->schedule_slot ? $date.', '.self::slotLabel($this->schedule_slot) : $date;
    }

    /** "08:00-09:00" becomes "8:00 AM – 9:00 AM". */
    public static function slotLabel(string $slot): string
    {
        [$from, $to] = explode('-', $slot);

        return Carbon::createFromFormat('H:i', $from)->format('g:i A').' – '.Carbon::createFromFormat('H:i', $to)->format('g:i A');
    }

    /**
     * Steps shown on the customer's tracking screen.
     *
     * @return array<int, array{label:string, done:bool, current:bool, tone:string, time:?Carbon}>
     */
    public function timeline(): array
    {
        $steps = $this->isDelivery()
            ? [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Assigned,
                OrderStatus::OutForDelivery, OrderStatus::Delivered, OrderStatus::Completed]
            : [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::ReadyForPickup,
                OrderStatus::Completed];

        $times = $this->histories->groupBy('to_status')->map(fn ($g) => $g->last()->created_at);

        $result = [];
        foreach ($steps as $step) {
            $result[] = [
                'label' => $step->label(),
                'done' => $times->has($step->value),
                'current' => $this->status === $step,
                'tone' => $step->tone(),
                'time' => $times->get($step->value),
            ];
        }

        if (in_array($this->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true)) {
            $result[] = [
                'label' => $this->status->label(),
                'done' => true,
                'current' => true,
                'tone' => 'bad',
                'time' => $times->get($this->status->value),
            ];
        }

        return $result;
    }
}
