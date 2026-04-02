<?php

namespace Database\Factories;

use App\Models\DiscountRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiscountRuleFactory extends Factory
{
    protected $model = DiscountRule::class;

    public function definition(): array
    {
        return [
            'name'           => $this->faker->words(3, true),
            'min_quantity'   => 10,
            'max_quantity'   => 19,
            'discount_type'  => 'percentage',
            'discount_value' => 10.00,
            'priority'       => 0,
            'is_active'      => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function percentage(float $value): static
    {
        return $this->state(['discount_type' => 'percentage', 'discount_value' => $value]);
    }

    public function fixedAmount(float $value): static
    {
        return $this->state(['discount_type' => 'fixed_amount', 'discount_value' => $value]);
    }
}
