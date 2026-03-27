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

        $hombreCamisas = Category::where('code', 'HOMBRE_CAMISAS')->first();
        $ninoCamisas = Category::where('code', 'NINO_CAMISAS')->first();
        $hombrePantalones = Category::where('code', 'HOMBRE_PANTALONES')->first();
        $ninoPantalones = Category::where('code', 'NINO_PANTALONES')->first();
        $hombreBermudas = Category::where('code', 'HOMBRE_BERMUDAS')->first();
        $ninoBermudas = Category::where('code', 'NINO_BERMUDAS')->first();
        $hombreConjuntos = Category::where('code', 'HOMBRE_CONJUNTOS')->first();
        $ninoConjuntos = Category::where('code', 'NINO_CONJUNTOS')->first();

        $this->createGrandChildren($hombreCamisas, [
            [
                'code' => 'HOMBRE_CAMISAS_MANGA_LARGA',
                'name' => 'Manga Larga',
                'description' => 'Camisas de manga larga',
                'sort_order' => 1,
            ],
            [
                'code' => 'HOMBRE_CAMISAS_MANGA_CORTA',
                'name' => 'Manga Corta',
                'description' => 'Camisas de manga corta',
                'sort_order' => 2,
            ],
            [
                'code' => 'HOMBRE_CAMISAS_SIN_MANGA',
                'name' => 'Sin Manga',
                'description' => 'Camisas sin manga',
                'sort_order' => 3,
            ],
            [
                'code' => 'HOMBRE_CAMISAS_POLO',
                'name' => 'Polo',
                'description' => 'Camisas tipo polo',
                'sort_order' => 4,
            ],
            [
                'code' => 'HOMBRE_CAMISAS_CUELLO_V',
                'name' => 'Cuello V',
                'description' => 'Camisas con cuello V',
                'sort_order' => 5,
            ],
            [
                'code' => 'HOMBRE_CAMISAS_CUELLO_TORTUGA',
                'name' => 'Cuello Tortuga',
                'description' => 'Camisas con cuello tortuga',
                'sort_order' => 6,
            ],
        ]);

        $this->createGrandChildren($ninoCamisas, [
            [
                'code' => 'NINO_CAMISAS_MANGA_LARGA',
                'name' => 'Manga Larga',
                'description' => 'Camisas de manga larga',
                'sort_order' => 1,
            ],
            [
                'code' => 'NINO_CAMISAS_MANGA_CORTA',
                'name' => 'Manga Corta',
                'description' => 'Camisas de manga corta',
                'sort_order' => 2,
            ],
            [
                'code' => 'NINO_CAMISAS_POLO',
                'name' => 'Polo',
                'description' => 'Camisas tipo polo',
                'sort_order' => 3,
            ],
        ]);

        $this->createGrandChildren($hombrePantalones, [
            [
                'code' => 'HOMBRE_PANTALONES_JEAN',
                'name' => 'Jean',
                'description' => 'Pantalones de jean',
                'sort_order' => 1,
            ],
            [
                'code' => 'HOMBRE_PANTALONES_ALGODON',
                'name' => 'Algodón',
                'description' => 'Pantalones de algodón',
                'sort_order' => 2,
            ],
            [
                'code' => 'HOMBRE_PANTALONES_MEZCLILLA',
                'name' => 'Mezclilla',
                'description' => 'Pantalones de mezclilla',
                'sort_order' => 3,
            ],
            [
                'code' => 'HOMBRE_PANTALONES_FORMAL',
                'name' => 'Formal',
                'description' => 'Pantalones formales',
                'sort_order' => 4,
            ],
            [
                'code' => 'HOMBRE_PANTALONES_DEPORTIVO',
                'name' => 'Deportivo',
                'description' => 'Pantalones deportivos',
                'sort_order' => 5,
            ],
        ]);

        $this->createGrandChildren($ninoPantalones, [
            [
                'code' => 'NINO_PANTALONES_JEAN',
                'name' => 'Jean',
                'description' => 'Pantalones de jean',
                'sort_order' => 1,
            ],
            [
                'code' => 'NINO_PANTALONES_DEPORTIVO',
                'name' => 'Deportivo',
                'description' => 'Pantalones deportivos',
                'sort_order' => 2,
            ],
            [
                'code' => 'NINO_PANTALONES_CARGO',
                'name' => 'Cargo',
                'description' => 'Pantalones cargo',
                'sort_order' => 3,
            ],
        ]);

        $this->createGrandChildren($hombreBermudas, [
            [
                'code' => 'HOMBRE_BERMUDAS_JEAN',
                'name' => 'Jean',
                'description' => 'Bermudas de jean',
                'sort_order' => 1,
            ],
            [
                'code' => 'HOMBRE_BERMUDAS_ALGODON',
                'name' => 'Algodón',
                'description' => 'Bermudas de algodón',
                'sort_order' => 2,
            ],
            [
                'code' => 'HOMBRE_BERMUDAS_DEPORTIVO',
                'name' => 'Deportivo',
                'description' => 'Bermudas deportivas',
                'sort_order' => 3,
            ],
            [
                'code' => 'HOMBRE_BERMUDAS_FORMAL',
                'name' => 'Formal',
                'description' => 'Bermudas formales',
                'sort_order' => 4,
            ],
        ]);

        $this->createGrandChildren($ninoBermudas, [
            [
                'code' => 'NINO_BERMUDAS_JEAN',
                'name' => 'Jean',
                'description' => 'Bermudas de jean',
                'sort_order' => 1,
            ],
            [
                'code' => 'NINO_BERMUDAS_DEPORTIVO',
                'name' => 'Deportivo',
                'description' => 'Bermudas deportivas',
                'sort_order' => 2,
            ],
        ]);

        $this->createGrandChildren($hombreConjuntos, [
            [
                'code' => 'HOMBRE_CONJUNTOS_CAMISA_PANTALON',
                'name' => 'Camisa + Pantalón',
                'description' => 'Conjunto camisa y pantalón',
                'sort_order' => 1,
            ],
            [
                'code' => 'HOMBRE_CONJUNTOS_CAMISA_BERMUDA',
                'name' => 'Camisa + Bermuda',
                'description' => 'Conjunto camisa y bermuda',
                'sort_order' => 2,
            ],
            [
                'code' => 'HOMBRE_CONJUNTOS_POLO_PANTALON',
                'name' => 'Polo + Pantalón',
                'description' => 'Conjunto polo y pantalón',
                'sort_order' => 3,
            ],
            [
                'code' => 'HOMBRE_CONJUNTOS_POLO_BERMUDA',
                'name' => 'Polo + Bermuda',
                'description' => 'Conjunto polo y bermuda',
                'sort_order' => 4,
            ],
        ]);

        $this->createGrandChildren($ninoConjuntos, [
            [
                'code' => 'NINO_CONJUNTOS_CAMISA_PANTALON',
                'name' => 'Camisa + Pantalón',
                'description' => 'Conjunto camisa y pantalón',
                'sort_order' => 1,
            ],
            [
                'code' => 'NINO_CONJUNTOS_CAMISA_BERMUDA',
                'name' => 'Camisa + Bermuda',
                'description' => 'Conjunto camisa y bermuda',
                'sort_order' => 2,
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
            [
                'code' => 'MUJER_PANTALONES',
                'name' => 'Pantalones',
                'description' => 'Pantalones para mujer',
                'sort_order' => 4,
            ],
            [
                'code' => 'MUJER_FALDAS',
                'name' => 'Faldas',
                'description' => 'Faldas para mujer',
                'sort_order' => 5,
            ],
        ]);

        $mujerBlusas = Category::where('code', 'MUJER_BLUSAS')->first();
        $mujerConjuntos = Category::where('code', 'MUJER_CONJUNTOS')->first();
        $mujerBlusasJean = Category::where('code', 'MUJER_BLUSA_JEAN')->first();
        $mujerPantalones = Category::where('code', 'MUJER_PANTALONES')->first();
        $mujerFaldas = Category::where('code', 'MUJER_FALDAS')->first();

        $this->createGrandChildren($mujerBlusas, [
            [
                'code' => 'MUJER_BLUSAS_MANGA_LARGA',
                'name' => 'Manga Larga',
                'description' => 'Blusas de manga larga',
                'sort_order' => 1,
            ],
            [
                'code' => 'MUJER_BLUSAS_MANGA_CORTA',
                'name' => 'Manga Corta',
                'description' => 'Blusas de manga corta',
                'sort_order' => 2,
            ],
            [
                'code' => 'MUJER_BLUSAS_SIN_MANGA',
                'name' => 'Sin Manga',
                'description' => 'Blusas sin manga',
                'sort_order' => 3,
            ],
            [
                'code' => 'MUJER_BLUSAS_CUELLO_V',
                'name' => 'Cuello V',
                'description' => 'Blusas con cuello V',
                'sort_order' => 4,
            ],
            [
                'code' => 'MUJER_BLUSAS_CUELLO_REDONDO',
                'name' => 'Cuello Redondo',
                'description' => 'Blusas con cuello redondo',
                'sort_order' => 5,
            ],
            [
                'code' => 'MUJER_BLUSAS_CAMISERA',
                'name' => 'Camisera',
                'description' => 'Blusas tipo camisera',
                'sort_order' => 6,
            ],
        ]);

        $this->createGrandChildren($mujerConjuntos, [
            [
                'code' => 'MUJER_CONJUNTOS_BLUSA_PANTALON',
                'name' => 'Blusa + Pantalón',
                'description' => 'Conjunto blusa y pantalón',
                'sort_order' => 1,
            ],
            [
                'code' => 'MUJER_CONJUNTOS_BLUSA_FALDA',
                'name' => 'Blusa + Falda',
                'description' => 'Conjunto blusa y falda',
                'sort_order' => 2,
            ],
            [
                'code' => 'MUJER_CONJUNTOS_TOP_PANTALON',
                'name' => 'Top + Pantalón',
                'description' => 'Conjunto top y pantalón',
                'sort_order' => 3,
            ],
            [
                'code' => 'MUJER_CONJUNTOS_TOP_FALDA',
                'name' => 'Top + Falda',
                'description' => 'Conjunto top y falda',
                'sort_order' => 4,
            ],
        ]);

        $this->createGrandChildren($mujerBlusasJean, [
            [
                'code' => 'MUJER_BLUSA_JEAN_MANGAS',
                'name' => 'Con Mangas',
                'description' => 'Blusas en jean con mangas',
                'sort_order' => 1,
            ],
            [
                'code' => 'MUJER_BLUSA_JEAN_SIN_MANGAS',
                'name' => 'Sin Mangas',
                'description' => 'Blusas en jean sin mangas',
                'sort_order' => 2,
            ],
            [
                'code' => 'MUJER_BLUSA_JEAN_CAMISERA',
                'name' => 'Camisera',
                'description' => 'Blusas en jean tipo camisera',
                'sort_order' => 3,
            ],
        ]);

        $this->createGrandChildren($mujerPantalones, [
            [
                'code' => 'MUJER_PANTALONES_JEAN',
                'name' => 'Jean',
                'description' => 'Pantalones de jean',
                'sort_order' => 1,
            ],
            [
                'code' => 'MUJER_PANTALONES_LEGGINGS',
                'name' => 'Leggings',
                'description' => 'Pantalones leggings',
                'sort_order' => 2,
            ],
            [
                'code' => 'MUJER_PANTALONES_FORMAL',
                'name' => 'Formal',
                'description' => 'Pantalones formales',
                'sort_order' => 3,
            ],
            [
                'code' => 'MUJER_PANTALONES_DEPORTIVO',
                'name' => 'Deportivo',
                'description' => 'Pantalones deportivos',
                'sort_order' => 4,
            ],
            [
                'code' => 'MUJER_PANTALONES_ANCHO',
                'name' => 'Ancho (Palazzo)',
                'description' => 'Pantalones anchos o palazzo',
                'sort_order' => 5,
            ],
        ]);

        $this->createGrandChildren($mujerFaldas, [
            [
                'code' => 'MUJER_FALDAS_JEAN',
                'name' => 'Jean',
                'description' => 'Faldas de jean',
                'sort_order' => 1,
            ],
            [
                'code' => 'MUJER_FALDAS_PLISADAS',
                'name' => 'Plisadas',
                'description' => 'Faldas plisadas',
                'sort_order' => 2,
            ],
            [
                'code' => 'MUJER_FALDAS_RECTAS',
                'name' => 'Rectas',
                'description' => 'Faldas rectas',
                'sort_order' => 3,
            ],
            [
                'code' => 'MUJER_FALDAS_CORTAS',
                'name' => 'Cortas',
                'description' => 'Faldas cortas',
                'sort_order' => 4,
            ],
            [
                'code' => 'MUJER_FALDAS_LARGAS',
                'name' => 'Largas',
                'description' => 'Faldas largas',
                'sort_order' => 5,
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

    private function createGrandChildren(Category $parent, array $children): void
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
