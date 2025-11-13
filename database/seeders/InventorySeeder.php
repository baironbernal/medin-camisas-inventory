<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\Store;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();
        $variants = ProductVariant::all();

        foreach ($stores as $store) {
            foreach ($variants as $variant) {
                $baseQuantity = rand(0, 100);

                Inventory::create([
                    'product_variant_id' => $variant->id,
                    'store_id' => $store->id,
                    'quantity_available' => $baseQuantity,
                    'quantity_reserved' => rand(0, 10),
                    'quantity_in_transit' => rand(0, 5),
                    'min_quantity' => 10,
                    'max_quantity' => 200,
                    'reorder_point' => 20,
                    'location' => $this->generateLocation(),
                    'last_restock_date' => now()->subDays(rand(1, 30)),
                    'last_sale_date' => now()->subDays(rand(1, 7)),
                    'last_inventory_check_date' => now()->subDays(rand(1, 15)),
                ]);
            }
        }
    }

    private function generateLocation(): string
    {
        $zone = ['A', 'B', 'C', 'D'][rand(0, 3)];
        $rack = rand(1, 10);
        $level = rand(1, 5);

        return "{$zone}{$rack}-{$level}";
    }
}


