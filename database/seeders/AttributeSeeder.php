<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        // Size attribute
        $size = Attribute::create([
            'code' => 'SIZE',
            'name' => 'Talla',
            'data_type' => 'text',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        foreach ($sizes as $index => $sizeValue) {
            AttributeValue::create([
                'attribute_id' => $size->id,
                'value' => $sizeValue,
                'code' => $sizeValue,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        // Color attribute
        $color = Attribute::create([
            'code' => 'COLOR',
            'name' => 'Color',
            'data_type' => 'color',
            'is_required' => true,
            'sort_order' => 2,
        ]);

        $colors = [
            ['value' => 'Negro', 'code' => 'BLK', 'hex' => '#000000'],
            ['value' => 'Blanco', 'code' => 'WHT', 'hex' => '#FFFFFF'],
            ['value' => 'Azul', 'code' => 'BLU', 'hex' => '#0000FF'],
            ['value' => 'Rojo', 'code' => 'RED', 'hex' => '#FF0000'],
            ['value' => 'Verde', 'code' => 'GRN', 'hex' => '#00FF00'],
            ['value' => 'Gris', 'code' => 'GRY', 'hex' => '#808080'],
            ['value' => 'Navy', 'code' => 'NVY', 'hex' => '#000080'],
            ['value' => 'Beige', 'code' => 'BGE', 'hex' => '#F5F5DC'],
        ];

        foreach ($colors as $index => $colorData) {
            AttributeValue::create([
                'attribute_id' => $color->id,
                'value' => $colorData['value'],
                'code' => $colorData['code'],
                'hex_color' => $colorData['hex'],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        // Material attribute
        $material = Attribute::create([
            'code' => 'MATERIAL',
            'name' => 'Material',
            'data_type' => 'text',
            'is_required' => true,
            'sort_order' => 3,
        ]);

        $materials = [
            ['value' => 'Algodón', 'code' => 'ALG'],
            ['value' => 'Poliéster', 'code' => 'POL'],
            ['value' => 'Lino', 'code' => 'LIN'],
            ['value' => 'Lana', 'code' => 'LAN'],
            ['value' => 'Mezclilla', 'code' => 'MEZ'],
            ['value' => 'Sintético', 'code' => 'SIN'],
        ];

        foreach ($materials as $index => $materialData) {
            AttributeValue::create([
                'attribute_id' => $material->id,
                'value' => $materialData['value'],
                'code' => $materialData['code'],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }
    }
}


