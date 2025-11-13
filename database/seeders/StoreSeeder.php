<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $stores = [
            [
                'code' => 'STORE-001',
                'name' => 'Tienda Centro',
                'address' => 'Cra. 13 #85-15, Local 201',
                'city' => 'Bogotá',
                'state' => 'Bogotá D.C.',
                'country' => 'Colombia',
                'postal_code' => '110221',
                'phone_number' => '+57 1 6234567',
                'email' => 'centro@inventory.com',
                'is_active' => true,
                'manager_name' => 'Carlos Rodríguez',
                'latitude' => 4.669764,
                'longitude' => -74.054596,
                'max_capacity' => 15000,
            ],
            [
                'code' => 'STORE-002',
                'name' => 'Tienda Norte',
                'address' => 'Av. 19 #120-71',
                'city' => 'Bogotá',
                'state' => 'Bogotá D.C.',
                'country' => 'Colombia',
                'postal_code' => '110111',
                'phone_number' => '+57 1 6234568',
                'email' => 'norte@inventory.com',
                'is_active' => true,
                'manager_name' => 'María González',
                'latitude' => 4.695234,
                'longitude' => -74.041230,
                'max_capacity' => 12000,
            ],
            [
                'code' => 'STORE-003',
                'name' => 'Tienda Sur',
                'address' => 'Cll. 140 #7-19',
                'city' => 'Bogotá',
                'state' => 'Bogotá D.C.',
                'country' => 'Colombia',
                'postal_code' => '110131',
                'phone_number' => '+57 1 6234569',
                'email' => 'sur@inventory.com',
                'is_active' => true,
                'manager_name' => 'Pedro Martínez',
                'latitude' => 4.625678,
                'longitude' => -74.072345,
                'max_capacity' => 10000,
            ],
        ];

        foreach ($stores as $store) {
            Store::create($store);
        }
    }
}


