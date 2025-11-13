<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone_number',
        'email',
        'is_active',
        'manager_name',
        'latitude',
        'longitude',
        'max_capacity',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'max_capacity' => 'integer',
    ];

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'assigned_store_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getCurrentCapacity(): int
    {
        return $this->inventories()->sum('quantity_available');
    }

    public function getCapacityPercentage(): float
    {
        if ($this->max_capacity == 0) {
            return 0;
        }

        return ($this->getCurrentCapacity() / $this->max_capacity) * 100;
    }
}


