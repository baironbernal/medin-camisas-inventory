<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $man = Category::create([
            'code' => 'HOMBRE',
            'name' => 'Hombre',
            'description' => 'Ropa para hombre',
            'parent_id' => null,
            'sort_order' => 1,
        ]);

        $boy = Category::create([
            'code' => 'NINO',
            'name' => 'Niño',
            'description' => 'Ropa para niño',
            'parent_id' => null,
            'sort_order' => 2,
        ]);

        $women = Category::create([
            'code' => 'MUJER',
            'name' => 'Mujer',
            'description' => 'Ropa para mujer',
            'parent_id' => null,
            'sort_order' => 3,
        ]);


        $this->createChildren($man, [
            [
                'code' => 'HOMBRE_CAMISAS',
                'name' => 'Camisas',
                'description' => 'Camisas para hombre',
                'sort_order' => 1,
            ],
            [
                'code' => 'HOMBRE_PANTALONES',
                'name' => 'Pantalones',
                'description' => 'Pantalones para hombre',
                'sort_order' => 2,
            ],
            [
                'code' => 'HOMBRE_BERMUDAS',
                'name' => 'Bermudas',
                'description' => 'Bermudas para hombre',
                'sort_order' => 3,
            ],
            [
                'code' => 'HOMBRE_CONJUNTOS',
                'name' => 'Conjuntos',
                'description' => 'Conjuntos para hombre',
                'sort_order' => 4,
            ],
        ]);
        $this->createChildren($boy, [
            [
                'code' => 'NINO_CAMISAS',
                'name' => 'Camisas',
                'description' => 'Camisas para niño',
                'sort_order' => 1,
            ],
            [
                'code' => 'NINO_PANTALONES',
                'name' => 'Pantalones',
                'description' => 'Pantalones para niño',
                'sort_order' => 2,
            ],
            [
                'code' => 'NINO_BERMUDAS',
                'name' => 'Bermudas',
                'description' => 'Bermudas para niño',
                'sort_order' => 3,
            ],
            [
                'code' => 'NINO_CONJUNTOS',
                'name' => 'Conjuntos',
                'description' => 'Conjuntos para niño',
                'sort_order' => 4,
            ],
        ]);
        $this->createChildren($women, [
            [
                'code' => 'MUJER_BLUSAS',
                'name' => 'Blusas',
                'description' => 'Blusas para mujer',
                'sort_order' => 1,
            ],
            [
                'code' => 'MUJER_CONJUNTOS',
                'name' => 'Conjuntos',
                'description' => 'Conjuntos para mujer',
                'sort_order' => 2,
            ],
            [
                'code' => 'MUJER_BLUSA_JEAN',
                'name' => 'Blusas en Jean',
                'description' => 'Blusas en jean para mujer',
                'sort_order' => 3,
            ],
        ]);
    }

    private function createChildren(Category $parent, array $children): void
        {
            foreach ($children as $child) {
                Category::create([
                    'code' => $child['code'],
                    'name' => $child['name'],
                    'description' => $child['description'],
                    'parent_id' => $parent->id,
                    'sort_order' => $child['sort_order'],
                ]);
            }
        }
}
    



