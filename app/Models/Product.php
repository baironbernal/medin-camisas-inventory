<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithMedia;

    protected $fillable = [
        'reference_code',
        'name',
        'slug',
        'description',
        'season_id',
        'category_id',
        'base_price',
        'brand',
        'supplier',
        'is_active',
        'images',
        'tags',
        'specifications',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'is_active' => 'boolean',
        'images' => 'json',
        'tags' => 'array',
        'specifications' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($product) {
            if ($product->isDirty('base_price')) {
                $product->variants()->update([
                    'price' => $product->base_price,
                ]);
            }
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function priceRules(): HasMany
    {
        return $this->hasMany(PriceRule::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInSeason($query, $seasonId)
    {
        return $query->where('season_id', $seasonId);
    }

    public function getTotalStockAttribute(): int
    {
        return $this->variants()
            ->with('inventories')
            ->get()
            ->sum(fn ($variant) => $variant->inventories->sum('quantity_available'));
    }

    public function getVariantCountAttribute(): int
    {
        return $this->variants()->count();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product-images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
