<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'product_variant_id'  => ProductVariant::factory(),
            'store_id'            => Store::factory(),
            'quantity_available'  => 50,
            'quantity_reserved'   => 0,
            'quantity_in_transit' => 0,
            'min_quantity'        => 5,
            'max_quantity'        => 100,
            'reorder_point'       => 10,
        ];
    }

    public function withStock(int $qty): static
    {
        return $this->state(['quantity_available' => $qty]);
    }

    public function outOfStock(): static
    {
        return $this->state(['quantity_available' => 0]);
    }

    public function lowStock(): static
    {
        return $this->state(['quantity_available' => 3, 'min_quantity' => 5]);
    }
}
