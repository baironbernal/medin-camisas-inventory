<?php

namespace BaironBernal\ColombiaLocations\Database\Seeders;

use Illuminate\Database\Seeder;

class ColombiaLocationsSeeder extends Seeder
{
    /**
     * Este es el seeder "orquestador". Llama a los demás en el orden correcto.
     * Primero departamentos (porque municipios dependen de ellos).
     */
    public function run(): void
    {
        $this->call([
            DepartamentosSeeder::class,
            MunicipiosSeeder::class,
        ]);
    }
}
