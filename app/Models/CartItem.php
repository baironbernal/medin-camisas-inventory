<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_variant_id',
        'quantity',
        'price_at_add',
    ];

    protected $casts = [
        'price_at_add' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->price_at_add * $this->quantity;
    }

    public function getAvailableStockAttribute(): int
    {
        return $this->productVariant?->total_stock ?? 0;
    }

    public function hasStock(?int $quantity = null): bool
    {
        $qty = $quantity ?? $this->quantity;
        return $this->available_stock >= $qty;
    }
}
