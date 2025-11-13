<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        // Owner
        $owner = User::create([
            'name' => 'owner',
            'first_name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'owner@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 1234567',
            'is_active' => true,
        ]);
        $owner->assignRole('owner');

        // Admin
        $admin = User::create([
            'name' => 'admin',
            'first_name' => 'Ana',
            'last_name' => 'García',
            'email' => 'admin@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 2345678',
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        // Inventory Manager
        $inventoryManager = User::create([
            'name' => 'inventory_manager',
            'first_name' => 'Luis',
            'last_name' => 'Ramírez',
            'email' => 'inventory@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 3456789',
            'is_active' => true,
        ]);
        $inventoryManager->assignRole('inventory_manager');

        // Store Supervisors (one per store)
        foreach ($stores as $index => $store) {
            $supervisor = User::create([
                'name' => 'supervisor_' . strtolower($store->code),
                'first_name' => 'Supervisor',
                'last_name' => $store->name,
                'email' => 'supervisor' . ($index + 1) . '@inventory.com',
                'password' => Hash::make('password'),
                'phone_number' => '+57 300 456789' . $index,
                'is_active' => true,
                'assigned_store_id' => $store->id,
            ]);
            $supervisor->assignRole('store_supervisor');
        }

        // Warehouse Operators
        $warehouseOperator1 = User::create([
            'name' => 'operator1',
            'first_name' => 'Carlos',
            'last_name' => 'López',
            'email' => 'operator1@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 5678901',
            'is_active' => true,
            'assigned_store_id' => $stores->first()->id,
        ]);
        $warehouseOperator1->assignRole('warehouse_operator');

        $warehouseOperator2 = User::create([
            'name' => 'operator2',
            'first_name' => 'Diana',
            'last_name' => 'Torres',
            'email' => 'operator2@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 6789012',
            'is_active' => true,
            'assigned_store_id' => $stores->skip(1)->first()->id,
        ]);
        $warehouseOperator2->assignRole('warehouse_operator');

        // Sellers
        $seller1 = User::create([
            'name' => 'seller1',
            'first_name' => 'Roberto',
            'last_name' => 'Mendoza',
            'email' => 'seller1@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 7890123',
            'is_active' => true,
            'assigned_store_id' => $stores->first()->id,
        ]);
        $seller1->assignRole('seller');

        $seller2 = User::create([
            'name' => 'seller2',
            'first_name' => 'Patricia',
            'last_name' => 'Vargas',
            'email' => 'seller2@inventory.com',
            'password' => Hash::make('password'),
            'phone_number' => '+57 300 8901234',
            'is_active' => true,
            'assigned_store_id' => $stores->last()->id,
        ]);
        $seller2->assignRole('seller');
    }
}


