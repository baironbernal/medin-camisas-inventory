<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (self $inventory) {
            $inventory->movements()->delete();
        });
    }

    protected $fillable = [
        'product_variant_id',
        'store_id',
        'quantity_available',
        'quantity_reserved',
        'quantity_in_transit',
        'min_quantity',
        'max_quantity',
        'reorder_point',
        'location',
        'last_restock_date',
        'last_sale_date',
        'last_inventory_check_date',
    ];

    protected $casts = [
        'quantity_available' => 'integer',
        'quantity_reserved' => 'integer',
        'quantity_in_transit' => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'reorder_point' => 'integer',
        'last_restock_date' => 'date',
        'last_sale_date' => 'date',
        'last_inventory_check_date' => 'date',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function getTotalQuantityAttribute(): int
    {
        return $this->quantity_available + $this->quantity_reserved + $this->quantity_in_transit;
    }

    public function getNeedsRestockAttribute(): bool
    {
        return $this->quantity_available <= $this->reorder_point;
    }

    public function getIsLowStockAttribute(): bool
    {
        if (! $this->min_quantity) {
            return false;
        }

        return $this->quantity_available <= $this->min_quantity;
    }

    public function getIsOverstockedAttribute(): bool
    {
        if (! $this->max_quantity) {
            return false;
        }

        return $this->quantity_available >= $this->max_quantity;
    }

    public function scopeLowStock($query)
    {
        return $query->whereNotNull('min_quantity')
            ->whereColumn('quantity_available', '<=', 'min_quantity');
    }

    public function scopeNeedsRestock($query)
    {
        return $query->where('reorder_point', '>', 0)
            ->whereColumn('quantity_available', '<=', 'reorder_point');
    }
}
