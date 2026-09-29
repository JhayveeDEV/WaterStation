<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = ['user_id', 'delivery_area_id', 'label', 'address_text', 'city', 'notes', 'lat', 'lng', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean', 'lat' => 'float', 'lng' => 'float'];
    }

    public function isPinned(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryArea(): BelongsTo
    {
        return $this->belongsTo(DeliveryArea::class);
    }

    /** One-line text stored on the order so later address edits don't rewrite history. */
    public function fullText(): string
    {
        return collect([$this->address_text, $this->deliveryArea?->name, $this->city])
            ->filter()
            ->implode(', ');
    }
}
