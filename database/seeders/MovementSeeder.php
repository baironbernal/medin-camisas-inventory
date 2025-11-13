<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Movement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class MovementSeeder extends Seeder
{
    public function run(): void
    {
        $inventories = Inventory::with(['productVariant', 'store'])->limit(50)->get();
        $users = User::all();

        foreach ($inventories as $inventory) {
            // Create some purchase movements
            for ($i = 0; $i < rand(1, 3); $i++) {
                $quantity = rand(50, 200);
                $quantityBefore = $inventory->quantity_available;

                Movement::create([
                    'type' => Movement::TYPE_PURCHASE,
                    'product_variant_id' => $inventory->product_variant_id,
                    'inventory_id' => $inventory->id,
                    'store_id' => $inventory->store_id,
                    'quantity' => $quantity,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityBefore + $quantity,
                    'unit_cost' => $inventory->productVariant->cost,
                    'total_cost' => $inventory->productVariant->cost * $quantity,
                    'reference_document' => 'OC-2024-' . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT),
                    'supplier_id' => 'SUPP-' . rand(1, 10),
                    'user_id' => $users->random()->id,
                    'notes' => 'Compra regular de inventario',
                    'created_at' => now()->subDays(rand(30, 60)),
                ]);
            }

            // Create some sale movements
            for ($i = 0; $i < rand(2, 5); $i++) {
                $quantity = rand(1, 20);
                $quantityBefore = $inventory->quantity_available;

                if ($quantityBefore >= $quantity) {
                    Movement::create([
                        'type' => Movement::TYPE_SALE,
                        'product_variant_id' => $inventory->product_variant_id,
                        'inventory_id' => $inventory->id,
                        'store_id' => $inventory->store_id,
                        'quantity' => -$quantity,
                        'quantity_before' => $quantityBefore,
                        'quantity_after' => $quantityBefore - $quantity,
                        'unit_cost' => $inventory->productVariant->price,
                        'total_cost' => $inventory->productVariant->price * $quantity,
                        'reference_document' => 'FACT-2024-' . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT),
                        'customer_id' => 'CUST-' . rand(1, 100),
                        'user_id' => $users->random()->id,
                        'notes' => 'Venta al por mayor',
                        'metadata' => [
                            'customer_name' => 'Cliente ' . rand(1, 100),
                            'payment_method' => ['cash', 'credit', 'transfer'][rand(0, 2)],
                        ],
                        'created_at' => now()->subDays(rand(1, 30)),
                    ]);
                }
            }
        }

        // Create some transfer movements
        $stores = Store::all();
        for ($i = 0; $i < 10; $i++) {
            $sourceInventory = Inventory::where('quantity_available', '>', 50)->inRandomOrder()->first();
            if (!$sourceInventory) continue;

            $destinationStore = $stores->where('id', '!=', $sourceInventory->store_id)->random();
            $destinationInventory = Inventory::where('store_id', $destinationStore->id)
                ->where('product_variant_id', $sourceInventory->product_variant_id)
                ->first();

            if ($destinationInventory) {
                $quantity = rand(5, 20);

                // Source movement (negative)
                Movement::create([
                    'type' => Movement::TYPE_TRANSFER,
                    'product_variant_id' => $sourceInventory->product_variant_id,
                    'inventory_id' => $sourceInventory->id,
                    'store_id' => $sourceInventory->store_id,
                    'destination_store_id' => $destinationStore->id,
                    'quantity' => -$quantity,
                    'quantity_before' => $sourceInventory->quantity_available,
                    'quantity_after' => $sourceInventory->quantity_available - $quantity,
                    'reference_document' => 'TRANS-2024-' . str_pad((string)rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'user_id' => $users->random()->id,
                    'notes' => 'Transferencia entre tiendas',
                    'metadata' => [
                        'transfer_reason' => 'Reabastecimiento',
                        'authorized_by' => 'Gerente de Inventario',
                    ],
                    'created_at' => now()->subDays(rand(1, 15)),
                ]);

                // Destination movement (positive)
                Movement::create([
                    'type' => Movement::TYPE_TRANSFER,
                    'product_variant_id' => $destinationInventory->product_variant_id,
                    'inventory_id' => $destinationInventory->id,
                    'store_id' => $destinationStore->id,
                    'destination_store_id' => $sourceInventory->store_id,
                    'quantity' => $quantity,
                    'quantity_before' => $destinationInventory->quantity_available,
                    'quantity_after' => $destinationInventory->quantity_available + $quantity,
                    'reference_document' => 'TRANS-2024-' . str_pad((string)rand(1, 999), 3, '0', STR_PAD_LEFT),
                    'user_id' => $users->random()->id,
                    'notes' => 'Recepción de transferencia',
                    'created_at' => now()->subDays(rand(1, 15)),
                ]);
            }
        }
    }
}


