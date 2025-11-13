<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductVariant extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'sku',
        'product_id',
        'price',
        'cost',
        'weight',
        'barcode',
        'qr_code',
        'is_active',
        'images',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'weight' => 'decimal:3',
        'is_active' => 'boolean',
        'images' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function variantAttributes(): HasMany
    {
        return $this->hasMany(VariantAttribute::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getTotalStockAttribute(): int
    {
        return $this->inventories->sum('quantity_available');
    }

    public function getAttributesTextAttribute(): string
    {
        return $this->variantAttributes()
            ->with(['attribute', 'attributeValue'])
            ->get()
            ->map(fn($va) => $va->attributeValue->value)
            ->join(' - ');
    }

    public function generateSku(): string
    {
        $productCode = $this->product->reference_code;
        $attributes = $this->variantAttributes()
            ->with('attributeValue')
            ->get()
            ->map(fn($va) => $va->attributeValue->code)
            ->join('-');

        return strtoupper("{$productCode}-{$attributes}");
    }

    public function calculatePrice(): float
    {
        $basePrice = $this->product->base_price;

        // Apply price rules
        $rules = PriceRule::where(function ($query) {
            $query->whereNull('product_id')
                ->orWhere('product_id', $this->product_id);
        })
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderBy('priority')
            ->get();

        $finalPrice = $basePrice;

        foreach ($rules as $rule) {
            if ($this->matchesRule($rule)) {
                if ($rule->modifier_type === 'percentage') {
                    $finalPrice *= (1 + $rule->modifier_value / 100);
                } else {
                    $finalPrice += $rule->modifier_value;
                }
            }
        }

        return $finalPrice;
    }

    private function matchesRule(PriceRule $rule): bool
    {
        if (!$rule->attribute_id || !$rule->attribute_value_id) {
            return true;
        }

        return $this->variantAttributes()
            ->where('attribute_id', $rule->attribute_id)
            ->where('attribute_value_id', $rule->attribute_value_id)
            ->exists();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('variant-images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}


