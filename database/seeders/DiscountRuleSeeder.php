<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DiscountRule;

class DiscountRuleSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 EXACT QUANTITY RULES (1 - 5)

        DiscountRule::create([
            'name' => '1 unidad',
            'min_quantity' => 1,
            'max_quantity' => 1,
            'discount_type' => 'percentage',
            'discount_value' => 0,
            'priority' => 1,
            'is_active' => true,
        ]);

        DiscountRule::create([
            'name' => '2 unidades',
            'min_quantity' => 2,
            'max_quantity' => 2,
            'discount_type' => 'percentage',
            'discount_value' => 25,
            'priority' => 2,
            'is_active' => true,
        ]);

        DiscountRule::create([
            'name' => '3 unidades',
            'min_quantity' => 3,
            'max_quantity' => 3,
            'discount_type' => 'percentage',
            'discount_value' => 41,
            'priority' => 3,
            'is_active' => true,
        ]);

        DiscountRule::create([
            'name' => '4 unidades',
            'min_quantity' => 4,
            'max_quantity' => 4,
            'discount_type' => 'percentage',
            'discount_value' => 51,
            'priority' => 4,
            'is_active' => true,
        ]);

        DiscountRule::create([
            'name' => '5 unidades',
            'min_quantity' => 5,
            'max_quantity' => 5,
            'discount_type' => 'percentage',
            'discount_value' => 58,
            'priority' => 5,
            'is_active' => true,
        ]);

        // 🔹 RANGE RULES

        DiscountRule::create([
            'name' => 'Precio Emprendedor (6-11)',
            'min_quantity' => 6,
            'max_quantity' => 11,
            'discount_type' => 'percentage',
            'discount_value' => 60,
            'priority' => 6,
            'is_active' => true,
        ]);

        DiscountRule::create([
            'name' => 'Precio Mayorista (12+)',
            'min_quantity' => 12,
            'max_quantity' => null,
            'discount_type' => 'percentage',
            'discount_value' => 62,
            'priority' => 7,
            'is_active' => true,
        ]);
    }
}