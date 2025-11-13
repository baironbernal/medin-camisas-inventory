<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movement extends Model
{
    use HasFactory;

    const TYPE_PURCHASE = 'purchase';
    const TYPE_SALE = 'sale';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_RETURN = 'return';
    const TYPE_DAMAGE = 'damage';
    const TYPE_PRODUCTION = 'production';

    protected $fillable = [
        'type',
        'product_variant_id',
        'inventory_id',
        'store_id',
        'destination_store_id',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'total_cost',
        'reference_document',
        'customer_id',
        'supplier_id',
        'user_id',
        'notes',
        'batch_number',
        'expiration_date',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiration_date' => 'date',
        'metadata' => 'array',
    ];

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function destinationStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'destination_store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function getIsPositiveAttribute(): bool
    {
        return in_array($this->type, [
            self::TYPE_PURCHASE,
            self::TYPE_RETURN,
            self::TYPE_PRODUCTION,
        ]) || ($this->type === self::TYPE_ADJUSTMENT && $this->quantity > 0);
    }

    public function getIsNegativeAttribute(): bool
    {
        return in_array($this->type, [
            self::TYPE_SALE,
            self::TYPE_DAMAGE,
        ]) || ($this->type === self::TYPE_ADJUSTMENT && $this->quantity < 0);
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PURCHASE => 'success',
            self::TYPE_SALE => 'info',
            self::TYPE_TRANSFER => 'warning',
            self::TYPE_ADJUSTMENT => 'gray',
            self::TYPE_RETURN => 'success',
            self::TYPE_DAMAGE => 'danger',
            self::TYPE_PRODUCTION => 'primary',
            default => 'secondary',
        };
    }
}


