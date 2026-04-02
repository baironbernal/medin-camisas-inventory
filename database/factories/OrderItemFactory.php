<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $qty   = $this->faker->numberBetween(1, 5);
        $price = $this->faker->randomFloat(2, 10000, 100000);

        return [
            'order_id'               => Order::factory(),
            'product_variant_id'     => ProductVariant::factory(),
            'product_name'           => $this->faker->words(3, true),
            'variant_sku'            => strtoupper($this->faker->bothify('SKU-###-??')),
            'quantity'               => $qty,
            'unit_price'             => $price,
            'total_price'            => round($price * $qty, 2),
            'discount_percentage'    => 0,
            'discounted_unit_price'  => $price,
            'discounted_total_price' => round($price * $qty, 2),
        ];
    }
}
