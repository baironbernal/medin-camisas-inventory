<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku'        => strtoupper($this->faker->unique()->bothify('SKU-###-??')),
            'price'      => $this->faker->randomFloat(2, 10000, 100000),
            'cost'       => $this->faker->randomFloat(2, 5000, 50000),
            'weight'     => $this->faker->randomFloat(3, 0.1, 2.0),
            'is_active'  => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function withPrice(float $price): static
    {
        return $this->state(['price' => $price]);
    }
}
