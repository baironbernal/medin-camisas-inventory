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
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | Get Required Data (Fail Safe)
            |--------------------------------------------------------------------------
            */

            $summerSeason = Season::where('code', 'SUMMER-2024')->firstOrFail();

            $camisasHombre = Category::where('code', 'HOMBRE_CAMISAS')->firstOrFail();
            $pantalonesHombre = Category::where('code', 'HOMBRE_PANTALONES')->firstOrFail();
            $conjuntosHombre = Category::where('code', 'HOMBRE_CONJUNTOS')->firstOrFail();
            $bermudasHombre = Category::where('code', 'HOMBRE_BERMUDAS')->firstOrFail();

            $camisasNino = Category::where('code', 'NINO_CAMISAS')->firstOrFail();
            $pantalonesNino = Category::where('code', 'NINO_PANTALONES')->firstOrFail();
            $bermudasNino = Category::where('code', 'NINO_BERMUDAS')->firstOrFail();
            $conjuntosNino = Category::where('code', 'NINO_CONJUNTOS')->firstOrFail();

            $blusasMujer = Category::where('code', 'MUJER_BLUSAS')->firstOrFail();
            $conjuntosMujer = Category::where('code', 'MUJER_CONJUNTOS')->firstOrFail();
            $blusaJeanMujer = Category::where('code', 'MUJER_BLUSA_JEAN')->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | Product 1: Camiseta Polo Medin
            |--------------------------------------------------------------------------
            */

            $product1 = Product::create([
                'reference_code' => 'POLO-MEDIN',
                'name' => 'Camiseta Polo Medin',
                'description' => 'Camiseta polo clásica con diseño moderno, perfecta para ocasiones casuales y semi-formales',
                'season_id' => $summerSeason->id,
                'category_id' => $camisasHombre->id,
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


            /*
            |--------------------------------------------------------------------------
            | Product 2: Pantalón Cargo Style
            |--------------------------------------------------------------------------
            */

            $product2 = Product::create([
                'reference_code' => 'CARGO-STYLE',
                'name' => 'Pantalón Cargo Style',
                'description' => 'Pantalón cargo con múltiples bolsillos, ideal para un look urbano',
                'season_id' => $summerSeason->id,
                'category_id' => $pantalonesHombre->id,
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


            /*
            |--------------------------------------------------------------------------
            | Product 3: Sudadera Urban
            |--------------------------------------------------------------------------
            */

            $product3 = Product::create([
                'reference_code' => 'SUDADERA-URBAN',
                'name' => 'Sudadera Urban',
                'description' => 'Sudadera con capucha, diseño moderno y cómodo',
                'season_id' => $summerSeason->id,
                'category_id' => $conjuntosHombre->id,
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


            /*
            |--------------------------------------------------------------------------
            | Product 4: Camisa Oxford Clásica
            |--------------------------------------------------------------------------
            */

            $product4 = Product::create([
                'reference_code' => 'OXFORD-CLASSIC',
                'name' => 'Camisa Oxford Clásica',
                'description' => 'Camisa Oxford de algodón con cuello abotonado, ideal para oficina o casual',
                'season_id' => $summerSeason->id,
                'category_id' => $camisasHombre->id,
                'base_price' => 195000,
                'cost' => 120000,
                'brand' => 'Urban Style',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['formal', 'oxford', 'algodón'],
                'specifications' => [
                    'weight' => 0.28,
                    'dimensions' => [
                        'length' => 72,
                        'width' => 52,
                        'height' => 2,
                    ],
                    'care_instructions' => 'Lavar a máquina 30°C',
                    'origin' => 'Colombia',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Product 5: Pantalón Chino Slim
            |--------------------------------------------------------------------------
            */

            $product5 = Product::create([
                'reference_code' => 'CHINO-SLIM',
                'name' => 'Pantalón Chino Slim',
                'description' => 'Pantalón chino corte slim fit, versátil para día a día',
                'season_id' => $summerSeason->id,
                'category_id' => $pantalonesHombre->id,
                'base_price' => 265000,
                'cost' => 165000,
                'brand' => 'Street Wear',
                'supplier' => 'Confecciones Bogotá',
                'is_active' => true,
                'tags' => ['chino', 'slim', 'casual'],
                'specifications' => [
                    'weight' => 0.42,
                    'dimensions' => [
                        'length' => 98,
                        'width' => 34,
                        'height' => 3,
                    ],
                    'care_instructions' => 'Lavar a máquina con agua tibia',
                    'origin' => 'Colombia',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Product 6: Camiseta Básica Cuello Redondo
            |--------------------------------------------------------------------------
            */

            $product6 = Product::create([
                'reference_code' => 'BASIC-TEE',
                'name' => 'Camiseta Básica Cuello Redondo',
                'description' => 'Camiseta básica de algodón peinado, cuello redondo, esencial de guardarropa',
                'season_id' => $summerSeason->id,
                'category_id' => $camisasHombre->id,
                'base_price' => 85000,
                'cost' => 45000,
                'brand' => 'Comfort Zone',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['básica', 'algodón', 'casual'],
                'specifications' => [
                    'weight' => 0.18,
                    'dimensions' => [
                        'length' => 68,
                        'width' => 48,
                        'height' => 2,
                    ],
                    'care_instructions' => 'Lavar a máquina agua fría',
                    'origin' => 'Colombia',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Product 7: Bermuda Casual Playa
            |--------------------------------------------------------------------------
            */

            $product7 = Product::create([
                'reference_code' => 'BERMUDA-PLAYA',
                'name' => 'Bermuda Casual Playa',
                'description' => 'Bermuda ligera para playa o piscina, secado rápido y cómoda',
                'season_id' => $summerSeason->id,
                'category_id' => $bermudasHombre->id,
                'base_price' => 175000,
                'cost' => 95000,
                'brand' => 'Street Wear',
                'supplier' => 'Importaciones Fashion',
                'is_active' => true,
                'tags' => ['bermuda', 'playa', 'verano'],
                'specifications' => [
                    'weight' => 0.32,
                    'dimensions' => [
                        'length' => 55,
                        'width' => 38,
                        'height' => 3,
                    ],
                    'care_instructions' => 'Lavar a mano, secar al aire',
                    'origin' => 'Colombia',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Product 8: Polo Rayas Marineras
            |--------------------------------------------------------------------------
            */

            $product8 = Product::create([
                'reference_code' => 'POLO-RAYAS',
                'name' => 'Polo Rayas Marineras',
                'description' => 'Polo con rayas horizontales estilo marineras, look fresco y veraniego',
                'season_id' => $summerSeason->id,
                'category_id' => $camisasHombre->id,
                'base_price' => 220000,
                'cost' => 135000,
                'brand' => 'Urban Style',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['polo', 'rayas', 'marineras'],
                'specifications' => [
                    'weight' => 0.24,
                    'dimensions' => [
                        'length' => 69,
                        'width' => 51,
                        'height' => 2,
                    ],
                    'care_instructions' => 'Lavar a máquina agua fría',
                    'origin' => 'Colombia',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Product 9: Pantalón Jeans Slim Fit
            |--------------------------------------------------------------------------
            */

            $product9 = Product::create([
                'reference_code' => 'JEANS-SLIM',
                'name' => 'Pantalón Jeans Slim Fit',
                'description' => 'Jeans corte slim fit en denim de calidad, resistente y con buen acabado',
                'season_id' => $summerSeason->id,
                'category_id' => $pantalonesHombre->id,
                'base_price' => 298000,
                'cost' => 185000,
                'brand' => 'Street Wear',
                'supplier' => 'Confecciones Bogotá',
                'is_active' => true,
                'tags' => ['jeans', 'denim', 'slim'],
                'specifications' => [
                    'weight' => 0.55,
                    'dimensions' => [
                        'length' => 102,
                        'width' => 33,
                        'height' => 3,
                    ],
                    'care_instructions' => 'Lavar del revés, agua fría',
                    'origin' => 'Colombia',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Product 10: Conjunto Deportivo Running
            |--------------------------------------------------------------------------
            */

            $product10 = Product::create([
                'reference_code' => 'CONJ-RUNNING',
                'name' => 'Conjunto Deportivo Running',
                'description' => 'Conjunto deportivo transpirable para running o gimnasio, camiseta y pantalón corto',
                'season_id' => $summerSeason->id,
                'category_id' => $conjuntosHombre->id,
                'base_price' => 189000,
                'cost' => 110000,
                'brand' => 'Comfort Zone',
                'supplier' => 'Importaciones Fashion',
                'is_active' => true,
                'tags' => ['deportivo', 'running', 'transpirable'],
                'specifications' => [
                    'weight' => 0.38,
                    'dimensions' => [
                        'length' => 70,
                        'width' => 50,
                        'height' => 4,
                    ],
                    'care_instructions' => 'Lavar a máquina agua fría',
                    'origin' => 'Colombia',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Products 11-35: More products across Hombre, Niño, Mujer
            |--------------------------------------------------------------------------
            */

            $product11 = Product::create([
                'reference_code' => 'CAMISA-LINO',
                'name' => 'Camisa de Lino Verano',
                'description' => 'Camisa fresca de lino, ideal para días calurosos y look casual elegante',
                'season_id' => $summerSeason->id,
                'category_id' => $camisasHombre->id,
                'base_price' => 285000,
                'cost' => 170000,
                'brand' => 'Urban Style',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['lino', 'verano', 'fresco'],
                'specifications' => [
                    'weight' => 0.26,
                    'dimensions' => ['length' => 74, 'width' => 54, 'height' => 2],
                    'care_instructions' => 'Lavar a mano o máquina suave',
                    'origin' => 'Colombia',
                ],
            ]);

            $product12 = Product::create([
                'reference_code' => 'CAMISA-MANGA-CORTA',
                'name' => 'Camisa Manga Corta Casual',
                'description' => 'Camisa manga corta estampada, perfecta para playa o paseo',
                'season_id' => $summerSeason->id,
                'category_id' => $camisasHombre->id,
                'base_price' => 165000,
                'cost' => 98000,
                'brand' => 'Street Wear',
                'supplier' => 'Confecciones Bogotá',
                'is_active' => true,
                'tags' => ['manga corta', 'casual', 'estampado'],
                'specifications' => [
                    'weight' => 0.22,
                    'dimensions' => ['length' => 66, 'width' => 50, 'height' => 2],
                    'care_instructions' => 'Lavar a máquina agua fría',
                    'origin' => 'Colombia',
                ],
            ]);

            $product13 = Product::create([
                'reference_code' => 'PANTALON-JOGGER',
                'name' => 'Pantalón Jogger Deportivo',
                'description' => 'Jogger con cintura elástica y puños en tobillo, cómodo para entrenar o salir',
                'season_id' => $summerSeason->id,
                'category_id' => $pantalonesHombre->id,
                'base_price' => 198000,
                'cost' => 118000,
                'brand' => 'Comfort Zone',
                'supplier' => 'Importaciones Fashion',
                'is_active' => true,
                'tags' => ['jogger', 'deportivo', 'elástico'],
                'specifications' => [
                    'weight' => 0.38,
                    'dimensions' => ['length' => 95, 'width' => 36, 'height' => 3],
                    'care_instructions' => 'Lavar a máquina agua fría',
                    'origin' => 'Colombia',
                ],
            ]);

            $product14 = Product::create([
                'reference_code' => 'PANTALON-FORMAL',
                'name' => 'Pantalón Formal Oficina',
                'description' => 'Pantalón de vestir corte recto, tela plana para oficina o eventos',
                'season_id' => $summerSeason->id,
                'category_id' => $pantalonesHombre->id,
                'base_price' => 345000,
                'cost' => 210000,
                'brand' => 'Urban Style',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['formal', 'oficina', 'vestir'],
                'specifications' => [
                    'weight' => 0.48,
                    'dimensions' => ['length' => 104, 'width' => 36, 'height' => 3],
                    'care_instructions' => 'Lavar en seco o máquina delicado',
                    'origin' => 'Colombia',
                ],
            ]);

            $product15 = Product::create([
                'reference_code' => 'BERMUDA-DENIM',
                'name' => 'Bermuda Denim Hombre',
                'description' => 'Bermuda en denim lavado, estilo casual urbano',
                'season_id' => $summerSeason->id,
                'category_id' => $bermudasHombre->id,
                'base_price' => 215000,
                'cost' => 128000,
                'brand' => 'Street Wear',
                'supplier' => 'Confecciones Bogotá',
                'is_active' => true,
                'tags' => ['bermuda', 'denim', 'casual'],
                'specifications' => [
                    'weight' => 0.4,
                    'dimensions' => ['length' => 52, 'width' => 40, 'height' => 3],
                    'care_instructions' => 'Lavar del revés agua fría',
                    'origin' => 'Colombia',
                ],
            ]);

            $product16 = Product::create([
                'reference_code' => 'CONJ-PYJAMA',
                'name' => 'Conjunto Pijama Hombre',
                'description' => 'Pijama de dos piezas en algodón suave, pantalón y camiseta',
                'season_id' => $summerSeason->id,
                'category_id' => $conjuntosHombre->id,
                'base_price' => 158000,
                'cost' => 92000,
                'brand' => 'Comfort Zone',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['pijama', 'hogar', 'algodón'],
                'specifications' => [
                    'weight' => 0.45,
                    'dimensions' => ['length' => 72, 'width' => 52, 'height' => 5],
                    'care_instructions' => 'Lavar a máquina agua tibia',
                    'origin' => 'Colombia',
                ],
            ]);

            $product17 = Product::create([
                'reference_code' => 'NINO-POLO',
                'name' => 'Polo Infantil Medin',
                'description' => 'Camiseta polo para niño, cómoda y resistente para el día a día',
                'season_id' => $summerSeason->id,
                'category_id' => $camisasNino->id,
                'base_price' => 125000,
                'cost' => 72000,
                'brand' => 'Urban Style',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['niño', 'polo', 'infantil'],
                'specifications' => [
                    'weight' => 0.18,
                    'dimensions' => ['length' => 52, 'width' => 38, 'height' => 2],
                    'care_instructions' => 'Lavar a máquina agua fría',
                    'origin' => 'Colombia',
                ],
            ]);

            $product18 = Product::create([
                'reference_code' => 'NINO-CAMISETA-BASICA',
                'name' => 'Camiseta Básica Niño',
                'description' => 'Camiseta de algodón suave para niño, cuello redondo',
                'season_id' => $summerSeason->id,
                'category_id' => $camisasNino->id,
                'base_price' => 55000,
                'cost' => 32000,
                'brand' => 'Comfort Zone',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['niño', 'básica', 'algodón'],
                'specifications' => [
                    'weight' => 0.12,
                    'dimensions' => ['length' => 48, 'width' => 36, 'height' => 2],
                    'care_instructions' => 'Lavar a máquina agua fría',
                    'origin' => 'Colombia',
                ],
            ]);

            $product19 = Product::create([
                'reference_code' => 'NINO-CAMISA-HAWAI',
                'name' => 'Camisa Hawai Infantil',
                'description' => 'Camisa estilo hawaiiana para niño, divertida y fresca para vacaciones',
                'season_id' => $summerSeason->id,
                'category_id' => $camisasNino->id,
                'base_price' => 98000,
                'cost' => 58000,
                'brand' => 'Street Wear',
                'supplier' => 'Importaciones Fashion',
                'is_active' => true,
                'tags' => ['niño', 'hawai', 'vacaciones'],
                'specifications' => [
                    'weight' => 0.15,
                    'dimensions' => ['length' => 50, 'width' => 40, 'height' => 2],
                    'care_instructions' => 'Lavar a mano',
                    'origin' => 'Colombia',
                ],
            ]);

            $product20 = Product::create([
                'reference_code' => 'NINO-PANTALON-JEANS',
                'name' => 'Pantalón Jeans Niño',
                'description' => 'Jeans infantil resistente, ideal para colegio y juego',
                'season_id' => $summerSeason->id,
                'category_id' => $pantalonesNino->id,
                'base_price' => 145000,
                'cost' => 85000,
                'brand' => 'Street Wear',
                'supplier' => 'Confecciones Bogotá',
                'is_active' => true,
                'tags' => ['niño', 'jeans', 'resistente'],
                'specifications' => [
                    'weight' => 0.35,
                    'dimensions' => ['length' => 72, 'width' => 28, 'height' => 3],
                    'care_instructions' => 'Lavar a máquina agua fría',
                    'origin' => 'Colombia',
                ],
            ]);

            $product21 = Product::create([
                'reference_code' => 'NINO-PANTALON-DEPORTIVO',
                'name' => 'Pantalón Deportivo Niño',
                'description' => 'Pantalón deportivo con elástico en cintura, cómodo para correr y jugar',
                'season_id' => $summerSeason->id,
                'category_id' => $pantalonesNino->id,
                'base_price' => 78000,
                'cost' => 45000,
                'brand' => 'Comfort Zone',
                'supplier' => 'Importaciones Fashion',
                'is_active' => true,
                'tags' => ['niño', 'deportivo', 'elástico'],
                'specifications' => [
                    'weight' => 0.22,
                    'dimensions' => ['length' => 68, 'width' => 30, 'height' => 3],
                    'care_instructions' => 'Lavar a máquina',
                    'origin' => 'Colombia',
                ],
            ]);

            $product22 = Product::create([
                'reference_code' => 'NINO-BERMUDA-PLAYA',
                'name' => 'Bermuda Playa Niño',
                'description' => 'Bermuda ligera para niño, secado rápido para playa y piscina',
                'season_id' => $summerSeason->id,
                'category_id' => $bermudasNino->id,
                'base_price' => 85000,
                'cost' => 48000,
                'brand' => 'Street Wear',
                'supplier' => 'Importaciones Fashion',
                'is_active' => true,
                'tags' => ['niño', 'bermuda', 'playa'],
                'specifications' => [
                    'weight' => 0.2,
                    'dimensions' => ['length' => 42, 'width' => 32, 'height' => 2],
                    'care_instructions' => 'Lavar a mano',
                    'origin' => 'Colombia',
                ],
            ]);

            $product23 = Product::create([
                'reference_code' => 'NINO-BERMUDA-CARGO',
                'name' => 'Bermuda Cargo Niño',
                'description' => 'Bermuda con bolsillos cargo para niño, estilo aventurero',
                'season_id' => $summerSeason->id,
                'category_id' => $bermudasNino->id,
                'base_price' => 115000,
                'cost' => 68000,
                'brand' => 'Urban Style',
                'supplier' => 'Confecciones Bogotá',
                'is_active' => true,
                'tags' => ['niño', 'bermuda', 'cargo'],
                'specifications' => [
                    'weight' => 0.28,
                    'dimensions' => ['length' => 45, 'width' => 34, 'height' => 3],
                    'care_instructions' => 'Lavar a máquina',
                    'origin' => 'Colombia',
                ],
            ]);

            $product24 = Product::create([
                'reference_code' => 'NINO-CONJ-DEPORTIVO',
                'name' => 'Conjunto Deportivo Niño',
                'description' => 'Conjunto short y camiseta para niño, ideal para deporte y recreación',
                'season_id' => $summerSeason->id,
                'category_id' => $conjuntosNino->id,
                'base_price' => 118000,
                'cost' => 68000,
                'brand' => 'Comfort Zone',
                'supplier' => 'Importaciones Fashion',
                'is_active' => true,
                'tags' => ['niño', 'deportivo', 'conjunto'],
                'specifications' => [
                    'weight' => 0.28,
                    'dimensions' => ['length' => 55, 'width' => 42, 'height' => 4],
                    'care_instructions' => 'Lavar a máquina agua fría',
                    'origin' => 'Colombia',
                ],
            ]);

            $product25 = Product::create([
                'reference_code' => 'NINO-CONJ-ESCOLAR',
                'name' => 'Conjunto Escolar Niño',
                'description' => 'Conjunto camisa y pantalón para uso escolar, presentable y cómodo',
                'season_id' => $summerSeason->id,
                'category_id' => $conjuntosNino->id,
                'base_price' => 165000,
                'cost' => 95000,
                'brand' => 'Urban Style',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['niño', 'escolar', 'formal'],
                'specifications' => [
                    'weight' => 0.42,
                    'dimensions' => ['length' => 70, 'width' => 45, 'height' => 4],
                    'care_instructions' => 'Lavar a máquina',
                    'origin' => 'Colombia',
                ],
            ]);

            $product26 = Product::create([
                'reference_code' => 'MUJER-BLUSA-FLORAL',
                'name' => 'Blusa Floral Mujer',
                'description' => 'Blusa con estampado floral suave, fresca y femenina',
                'season_id' => $summerSeason->id,
                'category_id' => $blusasMujer->id,
                'base_price' => 178000,
                'cost' => 105000,
                'brand' => 'Urban Style',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['mujer', 'blusa', 'floral'],
                'specifications' => [
                    'weight' => 0.2,
                    'dimensions' => ['length' => 62, 'width' => 48, 'height' => 2],
                    'care_instructions' => 'Lavar a mano o delicado',
                    'origin' => 'Colombia',
                ],
            ]);

            $product27 = Product::create([
                'reference_code' => 'MUJER-BLUSA-MANGA-LARGA',
                'name' => 'Blusa Manga Larga Mujer',
                'description' => 'Blusa elegante manga larga, tela fluida para oficina o salida',
                'season_id' => $summerSeason->id,
                'category_id' => $blusasMujer->id,
                'base_price' => 195000,
                'cost' => 115000,
                'brand' => 'Urban Style',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['mujer', 'blusa', 'elegante'],
                'specifications' => [
                    'weight' => 0.24,
                    'dimensions' => ['length' => 68, 'width' => 50, 'height' => 2],
                    'care_instructions' => 'Lavar a mano',
                    'origin' => 'Colombia',
                ],
            ]);

            $product28 = Product::create([
                'reference_code' => 'MUJER-TOP-CROP',
                'name' => 'Top Crop Mujer',
                'description' => 'Top corto de algodón, ideal para combinar con pantalón alto',
                'season_id' => $summerSeason->id,
                'category_id' => $blusasMujer->id,
                'base_price' => 92000,
                'cost' => 52000,
                'brand' => 'Street Wear',
                'supplier' => 'Importaciones Fashion',
                'is_active' => true,
                'tags' => ['mujer', 'top', 'crop'],
                'specifications' => [
                    'weight' => 0.14,
                    'dimensions' => ['length' => 42, 'width' => 44, 'height' => 2],
                    'care_instructions' => 'Lavar a máquina agua fría',
                    'origin' => 'Colombia',
                ],
            ]);

            $product29 = Product::create([
                'reference_code' => 'MUJER-CONJ-DEPORTIVO',
                'name' => 'Conjunto Deportivo Mujer',
                'description' => 'Conjunto top y short deportivo para mujer, transpirable y cómodo',
                'season_id' => $summerSeason->id,
                'category_id' => $conjuntosMujer->id,
                'base_price' => 168000,
                'cost' => 98000,
                'brand' => 'Comfort Zone',
                'supplier' => 'Importaciones Fashion',
                'is_active' => true,
                'tags' => ['mujer', 'deportivo', 'gym'],
                'specifications' => [
                    'weight' => 0.32,
                    'dimensions' => ['length' => 58, 'width' => 46, 'height' => 4],
                    'care_instructions' => 'Lavar a máquina agua fría',
                    'origin' => 'Colombia',
                ],
            ]);

            $product30 = Product::create([
                'reference_code' => 'MUJER-CONJ-PLAYERO',
                'name' => 'Conjunto Playero Mujer',
                'description' => 'Conjunto bikini o enterizo estilo playero, tela resistente al cloro',
                'season_id' => $summerSeason->id,
                'category_id' => $conjuntosMujer->id,
                'base_price' => 145000,
                'cost' => 85000,
                'brand' => 'Street Wear',
                'supplier' => 'Importaciones Fashion',
                'is_active' => true,
                'tags' => ['mujer', 'playa', 'verano'],
                'specifications' => [
                    'weight' => 0.18,
                    'dimensions' => ['length' => 50, 'width' => 42, 'height' => 3],
                    'care_instructions' => 'Lavar a mano, secar al aire',
                    'origin' => 'Colombia',
                ],
            ]);

            $product31 = Product::create([
                'reference_code' => 'MUJER-BLUSA-JEAN-CLARO',
                'name' => 'Blusa Jean Claro Mujer',
                'description' => 'Blusa en tela tipo jean claro, estilo casual western',
                'season_id' => $summerSeason->id,
                'category_id' => $blusaJeanMujer->id,
                'base_price' => 185000,
                'cost' => 108000,
                'brand' => 'Street Wear',
                'supplier' => 'Confecciones Bogotá',
                'is_active' => true,
                'tags' => ['mujer', 'jean', 'casual'],
                'specifications' => [
                    'weight' => 0.32,
                    'dimensions' => ['length' => 60, 'width' => 48, 'height' => 2],
                    'care_instructions' => 'Lavar del revés agua fría',
                    'origin' => 'Colombia',
                ],
            ]);

            $product32 = Product::create([
                'reference_code' => 'MUJER-BLUSA-JEAN-OSCURO',
                'name' => 'Blusa Jean Oscuro Mujer',
                'description' => 'Blusa en denim oscuro, cuello redondo y botones decorativos',
                'season_id' => $summerSeason->id,
                'category_id' => $blusaJeanMujer->id,
                'base_price' => 198000,
                'cost' => 118000,
                'brand' => 'Urban Style',
                'supplier' => 'Confecciones Bogotá',
                'is_active' => true,
                'tags' => ['mujer', 'jean', 'denim'],
                'specifications' => [
                    'weight' => 0.35,
                    'dimensions' => ['length' => 62, 'width' => 50, 'height' => 2],
                    'care_instructions' => 'Lavar del revés, agua fría',
                    'origin' => 'Colombia',
                ],
            ]);

            $product33 = Product::create([
                'reference_code' => 'CAMISA-VESTIR',
                'name' => 'Camisa de Vestir Hombre',
                'description' => 'Camisa formal de vestir, cuello italiano, para eventos y oficina',
                'season_id' => $summerSeason->id,
                'category_id' => $camisasHombre->id,
                'base_price' => 268000,
                'cost' => 162000,
                'brand' => 'Urban Style',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['formal', 'vestir', 'oficina'],
                'specifications' => [
                    'weight' => 0.3,
                    'dimensions' => ['length' => 76, 'width' => 56, 'height' => 2],
                    'care_instructions' => 'Planchar en húmedo',
                    'origin' => 'Colombia',
                ],
            ]);

            $product34 = Product::create([
                'reference_code' => 'PANTALON-CORTE-RELOJ',
                'name' => 'Pantalón Corte Reloj Hombre',
                'description' => 'Pantalón corte reloj clásico, cómodo y versátil',
                'season_id' => $summerSeason->id,
                'category_id' => $pantalonesHombre->id,
                'base_price' => 245000,
                'cost' => 148000,
                'brand' => 'Street Wear',
                'supplier' => 'Confecciones Bogotá',
                'is_active' => true,
                'tags' => ['corte reloj', 'clásico', 'casual'],
                'specifications' => [
                    'weight' => 0.44,
                    'dimensions' => ['length' => 100, 'width' => 36, 'height' => 3],
                    'care_instructions' => 'Lavar a máquina',
                    'origin' => 'Colombia',
                ],
            ]);

            $product35 = Product::create([
                'reference_code' => 'CONJ-HOME-OFFICE',
                'name' => 'Conjunto Home Office Hombre',
                'description' => 'Conjunto cómodo para trabajar en casa: pantalón y camiseta tipo polo',
                'season_id' => $summerSeason->id,
                'category_id' => $conjuntosHombre->id,
                'base_price' => 225000,
                'cost' => 132000,
                'brand' => 'Comfort Zone',
                'supplier' => 'Textiles Colombia S.A.',
                'is_active' => true,
                'tags' => ['home office', 'confort', 'teletrabajo'],
                'specifications' => [
                    'weight' => 0.52,
                    'dimensions' => ['length' => 74, 'width' => 54, 'height' => 5],
                    'care_instructions' => 'Lavar a máquina',
                    'origin' => 'Colombia',
                ],
            ]);


            /*
            |--------------------------------------------------------------------------
            | Price Rules
            |--------------------------------------------------------------------------
            */

            $materialAttr = Attribute::where('code', 'MATERIAL')->firstOrFail();
            $sizeAttr = Attribute::where('code', 'SIZE')->firstOrFail();

            PriceRule::create([
                'name' => 'Material Premium - Lino',
                'product_id' => null,
                'attribute_id' => $materialAttr->id,
                'attribute_value_id' => AttributeValue::where('code', 'LIN')->firstOrFail()->id,
                'modifier_type' => 'percentage',
                'modifier_value' => 30,
                'priority' => 1,
                'is_active' => true,
            ]);

            PriceRule::create([
                'name' => 'Talla XXL',
                'product_id' => null,
                'attribute_id' => $sizeAttr->id,
                'attribute_value_id' => AttributeValue::where('code', 'XXL')->firstOrFail()->id,
                'modifier_type' => 'fixed_amount',
                'modifier_value' => 40000,
                'priority' => 2,
                'is_active' => true,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Create Variants
            |--------------------------------------------------------------------------
            */

            $this->createVariantsForProduct($product1);
            $this->createVariantsForProduct($product2);
            $this->createVariantsForProduct($product3);
            $this->createVariantsForProduct($product4);
            $this->createVariantsForProduct($product5);
            $this->createVariantsForProduct($product6);
            $this->createVariantsForProduct($product7);
            $this->createVariantsForProduct($product8);
            $this->createVariantsForProduct($product9);
            $this->createVariantsForProduct($product10);
            $this->createVariantsForProduct($product11);
            $this->createVariantsForProduct($product12);
            $this->createVariantsForProduct($product13);
            $this->createVariantsForProduct($product14);
            $this->createVariantsForProduct($product15);
            $this->createVariantsForProduct($product16);
            $this->createVariantsForProduct($product17);
            $this->createVariantsForProduct($product18);
            $this->createVariantsForProduct($product19);
            $this->createVariantsForProduct($product20);
            $this->createVariantsForProduct($product21);
            $this->createVariantsForProduct($product22);
            $this->createVariantsForProduct($product23);
            $this->createVariantsForProduct($product24);
            $this->createVariantsForProduct($product25);
            $this->createVariantsForProduct($product26);
            $this->createVariantsForProduct($product27);
            $this->createVariantsForProduct($product28);
            $this->createVariantsForProduct($product29);
            $this->createVariantsForProduct($product30);
            $this->createVariantsForProduct($product31);
            $this->createVariantsForProduct($product32);
            $this->createVariantsForProduct($product33);
            $this->createVariantsForProduct($product34);
            $this->createVariantsForProduct($product35);

        });
    }


    /*
    |--------------------------------------------------------------------------
    | Variants Generator
    |--------------------------------------------------------------------------
    */

    private function createVariantsForProduct(Product $product): void
    {
        $sizeAttr = Attribute::where('code', 'SIZE')->firstOrFail();
        $colorAttr = Attribute::where('code', 'COLOR')->firstOrFail();
        $materialAttr = Attribute::where('code', 'MATERIAL')->firstOrFail();


        $sizes = AttributeValue::where('attribute_id', $sizeAttr->id)
            ->whereIn('code', ['S', 'M', 'L', 'XL'])
            ->get();

        $colors = AttributeValue::where('attribute_id', $colorAttr->id)
            ->whereIn('code', ['BLK', 'WHT', 'BLU', 'GRY'])
            ->get();

        $materials = AttributeValue::where('attribute_id', $materialAttr->id)
            ->whereIn('code', ['ALG', 'POL'])
            ->get();


        foreach ($sizes as $size) {
            foreach ($colors as $color) {
                foreach ($materials as $material) {

                    $variant = ProductVariant::create([
                        'sku' => $this->generateSku(
                            $product->reference_code,
                            [$size, $color, $material]
                        ),
                        'product_id' => $product->id,
                        'price' => $this->calculateVariantPrice(
                            $product->base_price,
                            [$size, $color, $material]
                        ),
                        'cost' => $product->cost,
                        'weight' => 0.3,
                        'barcode' => $this->generateBarcode(),
                        'is_active' => true,
                    ]);


                    VariantAttribute::insert([
                        [
                            'product_variant_id' => $variant->id,
                            'attribute_id' => $sizeAttr->id,
                            'attribute_value_id' => $size->id,
                        ],
                        [
                            'product_variant_id' => $variant->id,
                            'attribute_id' => $colorAttr->id,
                            'attribute_value_id' => $color->id,
                        ],
                        [
                            'product_variant_id' => $variant->id,
                            'attribute_id' => $materialAttr->id,
                            'attribute_value_id' => $material->id,
                        ],
                    ]);
                }
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function generateSku(string $productCode, array $attributeValues): string
    {
        return strtoupper(
            $productCode . '-' .
            collect($attributeValues)->pluck('code')->implode('-')
        );
    }


    private function calculateVariantPrice(float $basePrice, array $attributeValues): float
    {
        $price = $basePrice;

        foreach ($attributeValues as $value) {

            if ($value->code === 'XXL') {
                $price += 40000;
            }

            if ($value->code === 'LIN') {
                $price *= 1.3;
            }
        }

        return round($price, 2);
    }


    private function generateBarcode(): string
    {
        return '750' . str_pad(
            (string) rand(1, 999999999),
            9,
            '0',
            STR_PAD_LEFT
        );
    }
}