<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'SHIRTS',
                'name' => 'Camisas',
                'description' => 'Camisas para hombre y mujer',
                'parent_id' => null,
                'sort_order' => 1,
            ],
            [
                'code' => 'PANTS',
                'name' => 'Pantalones',
                'description' => 'Pantalones de todos los estilos',
                'parent_id' => null,
                'sort_order' => 2,
            ],
            [
                'code' => 'SWEATERS',
                'name' => 'Sudaderas',
                'description' => 'Sudaderas y hoodies',
                'parent_id' => null,
                'sort_order' => 3,
            ],
            [
                'code' => 'ACCESSORIES',
                'name' => 'Accesorios',
                'description' => 'Complementos de moda',
                'parent_id' => null,
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = Category::create($categoryData);

            // Create subcategories
            if ($category->code === 'SHIRTS') {
                Category::create([
                    'code' => 'POLO',
                    'name' => 'Polos',
                    'description' => 'Camisas tipo polo',
                    'parent_id' => $category->id,
                    'sort_order' => 1,
                ]);
                Category::create([
                    'code' => 'T-SHIRT',
                    'name' => 'Camisetas',
                    'description' => 'Camisetas básicas',
                    'parent_id' => $category->id,
                    'sort_order' => 2,
                ]);
            }

            if ($category->code === 'PANTS') {
                Category::create([
                    'code' => 'JEANS',
                    'name' => 'Jeans',
                    'description' => 'Pantalones de mezclilla',
                    'parent_id' => $category->id,
                    'sort_order' => 1,
                ]);
                Category::create([
                    'code' => 'CARGO',
                    'name' => 'Cargo',
                    'description' => 'Pantalones cargo',
                    'parent_id' => $category->id,
                    'sort_order' => 2,
                ]);
            }
        }
    }
}


