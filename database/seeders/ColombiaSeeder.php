<?php

namespace Database\Seeders;

use BaironBernal\ColombiaLocations\Models\Departamento;
use BaironBernal\ColombiaLocations\Models\Municipio;
use Illuminate\Database\Seeder;

class ColombiaSeeder extends Seeder
{
    public function run(): void
    {
        if (Departamento::exists() && Municipio::exists()) {
            $this->command->info('Colombia locations already seeded — skipping.');
            return;
        }

        $vendorPath = base_path('vendor/baironbernal/colombia-locations/database/seeders');

        require_once $vendorPath . '/DepartamentosSeeder.php';
        require_once $vendorPath . '/MunicipiosSeeder.php';

        (new \BaironBernal\ColombiaLocations\Database\Seeders\DepartamentosSeeder())->run();
        (new \BaironBernal\ColombiaLocations\Database\Seeders\MunicipiosSeeder())->run();

        $this->command->info('Seeded ' . Departamento::count() . ' departments and ' . Municipio::count() . ' municipalities.');
    }
}
