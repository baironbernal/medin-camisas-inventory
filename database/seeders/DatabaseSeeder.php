<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            StoreSeeder::class,
            UserSeeder::class,
            SeasonSeeder::class,
            CategorySeeder::class,
            AttributeSeeder::class,
            ProductSeeder::class,
            InventorySeeder::class,
            MovementSeeder::class,
        ]);
    }
}
