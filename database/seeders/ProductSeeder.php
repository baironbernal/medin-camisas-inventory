<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\PriceRule;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Season;
use App\Models\VariantAttribute;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $summerSeason = Season::where('code', 'SUMMER-2024')->first();
        $poloCategory = Category::where('code', 'POLO')->first();
        $jeansCategory = Category::where('code', 'JEANS')->first();
        $sweatersCategory = Category::where('code', 'SWEATERS')->first();

        // Product 1: Camiseta Polo Medin
        $product1 = Product::create([
            'reference_code' => 'POLO-MEDIN',
            'name' => 'Camiseta Polo Medin',
            'description' => 'Camiseta polo clásica con diseño moderno, perfecta para ocasiones casuales y semi-formales',
            'season_id' => $summerSeason->id,
            'category_id' => $poloCategory->id,
            'base_price' => 250000,
            'cost' => 150000,
            'brand' => 'Urban Style',
            'supplier' => 'Textiles Colombia S.A.',
            'is_active' => true,
            'tags' => ['nuevo', 'popular', 'verano'],
            'specifications' => [
                'weight' => 0.25,
                'dimensions' => [
                    'length' => 70,
                    'width' => 50,
                    'height' => 2,
                ],
                'care_instructions' => 'Lavar a máquina con agua fría',
                'origin' => 'Colombia',
            ],
        ]);

        // Product 2: Pantalón Cargo Style
        $product2 = Product::create([
            'reference_code' => 'CARGO-STYLE',
            'name' => 'Pantalón Cargo Style',
            'description' => 'Pantalón cargo con múltiples bolsillos, ideal para un look urbano',
            'season_id' => $summerSeason->id,
            'category_id' => $jeansCategory->id,
            'base_price' => 320000,
            'cost' => 200000,
            'brand' => 'Street Wear',
            'supplier' => 'Confecciones Bogotá',
            'is_active' => true,
            'tags' => ['trending', 'cargo', 'urbano'],
            'specifications' => [
                'weight' => 0.45,
                'dimensions' => [
                    'length' => 100,
                    'width' => 35,
                    'height' => 3,
                ],
                'care_instructions' => 'Lavar a mano o máquina con agua tibia',
                'origin' => 'Colombia',
            ],
        ]);

        // Product 3: Sudadera Urban
        $product3 = Product::create([
            'reference_code' => 'SUDADERA-URBAN',
            'name' => 'Sudadera Urban',
            'description' => 'Sudadera con capucha, diseño moderno y cómodo',
            'season_id' => $summerSeason->id,
            'category_id' => $sweatersCategory->id,
            'base_price' => 280000,
            'cost' => 180000,
            'brand' => 'Comfort Zone',
            'supplier' => 'Importaciones Fashion',
            'is_active' => true,
            'tags' => ['comfort', 'casual', 'hoodie'],
            'specifications' => [
                'weight' => 0.5,
                'dimensions' => [
                    'length' => 75,
                    'width' => 55,
                    'height' => 5,
                ],
                'care_instructions' => 'Lavar del revés con agua fría',
                'origin' => 'Colombia',
            ],
        ]);

        // Create price rules
        PriceRule::create([
            'name' => 'Material Premium - Lino',
            'product_id' => null, // Applies to all products
            'attribute_id' => Attribute::where('code', 'MATERIAL')->first()->id,
            'attribute_value_id' => AttributeValue::where('code', 'LIN')->first()->id,
            'modifier_type' => 'percentage',
            'modifier_value' => 30,
            'priority' => 1,
            'is_active' => true,
        ]);

        PriceRule::create([
            'name' => 'Talla XXL',
            'product_id' => null,
            'attribute_id' => Attribute::where('code', 'SIZE')->first()->id,
            'attribute_value_id' => AttributeValue::where('code', 'XXL')->first()->id,
            'modifier_type' => 'fixed_amount',
            'modifier_value' => 40000,
            'priority' => 2,
            'is_active' => true,
        ]);

        // Create variants for each product
        $this->createVariantsForProduct($product1);
        $this->createVariantsForProduct($product2);
        $this->createVariantsForProduct($product3);
    }

    private function createVariantsForProduct(Product $product): void
    {
        $sizes = AttributeValue::where('attribute_id', Attribute::where('code', 'SIZE')->first()->id)
            ->whereIn('code', ['S', 'M', 'L', 'XL'])
            ->get();

        $colors = AttributeValue::where('attribute_id', Attribute::where('code', 'COLOR')->first()->id)
            ->whereIn('code', ['BLK', 'WHT', 'BLU', 'GRY'])
            ->get();

        $materials = AttributeValue::where('attribute_id', Attribute::where('code', 'MATERIAL')->first()->id)
            ->whereIn('code', ['ALG', 'POL'])
            ->get();

        foreach ($sizes as $size) {
            foreach ($colors as $color) {
                foreach ($materials as $material) {
                    $variant = ProductVariant::create([
                        'sku' => $this->generateSku($product->reference_code, [$size, $color, $material]),
                        'product_id' => $product->id,
                        'price' => $this->calculateVariantPrice($product->base_price, [$size, $color, $material]),
                        'cost' => $product->cost,
                        'weight' => 0.3,
                        'barcode' => $this->generateBarcode(),
                        'is_active' => true,
                    ]);

                    // Create variant attributes
                    VariantAttribute::create([
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $size->attribute_id,
                        'attribute_value_id' => $size->id,
                    ]);

                    VariantAttribute::create([
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $color->attribute_id,
                        'attribute_value_id' => $color->id,
                    ]);

                    VariantAttribute::create([
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $material->attribute_id,
                        'attribute_value_id' => $material->id,
                    ]);
                }
            }
        }
    }

    private function generateSku(string $productCode, array $attributeValues): string
    {
        $codes = array_map(fn($av) => $av->code, $attributeValues);
        return strtoupper($productCode . '-' . implode('-', $codes));
    }

    private function calculateVariantPrice(float $basePrice, array $attributeValues): float
    {
        $price = $basePrice;

        // Apply simple rules for demo
        foreach ($attributeValues as $value) {
            if ($value->code === 'XXL') {
                $price += 40000;
            }
            if ($value->code === 'LIN') {
                $price *= 1.3;
            }
        }

        return $price;
    }

    private function generateBarcode(): string
    {
        return '750' . str_pad((string)rand(1, 999999999), 9, '0', STR_PAD_LEFT);
    }
}


