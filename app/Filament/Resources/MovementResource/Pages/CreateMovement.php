<?php

namespace App\Filament\Resources\MovementResource\Pages;

use App\Filament\Resources\MovementResource;
use App\Models\Inventory;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateMovement extends CreateRecord
{
    protected static string $resource = MovementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Find or create the inventory record
        $inventory = Inventory::firstOrCreate(
            [
                'product_variant_id' => $data['product_variant_id'],
                'store_id' => $data['store_id'],
            ],
            [
                'quantity_available' => 0,
                'quantity_reserved' => 0,
                'quantity_in_transit' => 0,
                'min_quantity' => 0,
                'max_quantity' => 100,
                'reorder_point' => 0,
            ]
        );

        // Set the inventory_id
        $data['inventory_id'] = $inventory->id;

        // Get current stock (quantity_before)
        $data['quantity_before'] = $inventory->quantity_available;

        // Calculate quantity_after based on movement type
        $quantity = $data['quantity'] ?? 0;
        $type = $data['type'];

        // Double-check stock for sales, damages, and transfers
        if (in_array($type, ['sale', 'damage', 'transfer'])) {
            if ($quantity > $inventory->quantity_available) {
                Notification::make()
                    ->danger()
                    ->title('Stock Insuficiente')
                    ->body("No puede realizar esta operación. Solo hay {$inventory->quantity_available} unidades disponibles.")
                    ->persistent()
                    ->send();
                
                $this->halt();
            }
        }

        $data['quantity_after'] = match ($type) {
            'purchase', 'return', 'adjustment' => $data['quantity_before'] + $quantity,
            'sale', 'damage' => $data['quantity_before'] - $quantity,
            'transfer' => $data['quantity_before'] - $quantity, // Salida de la tienda origen
            default => $data['quantity_before'],
        };

        // Set authenticated user
        $data['user_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $movement = $this->record;

        DB::transaction(function () use ($movement) {
            // Update inventory quantity
            $inventory = Inventory::find($movement->inventory_id);
            
            if ($inventory) {
                $inventory->quantity_available = $movement->quantity_after;
                
                // Update last dates
                if (in_array($movement->type, ['purchase', 'return'])) {
                    $inventory->last_restock_date = now();
                } elseif ($movement->type === 'sale') {
                    $inventory->last_sale_date = now();
                }
                
                $inventory->save();

                // If it's a transfer, also create entry movement in destination store
                if ($movement->type === 'transfer' && $movement->destination_store_id) {
                    $destinationInventory = Inventory::firstOrCreate(
                        [
                            'product_variant_id' => $movement->product_variant_id,
                            'store_id' => $movement->destination_store_id,
                        ],
                        [
                            'quantity_available' => 0,
                            'quantity_reserved' => 0,
                            'quantity_in_transit' => 0,
                            'min_quantity' => 0,
                            'max_quantity' => 100,
                            'reorder_point' => 0,
                        ]
                    );

                    // Create entry movement in destination store
                    \App\Models\Movement::create([
                        'type' => 'transfer',
                        'product_variant_id' => $movement->product_variant_id,
                        'inventory_id' => $destinationInventory->id,
                        'store_id' => $movement->destination_store_id,
                        'quantity' => $movement->quantity,
                        'quantity_before' => $destinationInventory->quantity_available,
                        'quantity_after' => $destinationInventory->quantity_available + $movement->quantity,
                        'unit_cost' => $movement->unit_cost,
                        'total_cost' => $movement->total_cost,
                        'reference_document' => $movement->reference_document,
                        'user_id' => $movement->user_id,
                        'notes' => 'Transferencia recibida desde ' . $movement->store->name,
                    ]);

                    // Update destination inventory
                    $destinationInventory->quantity_available += $movement->quantity;
                    $destinationInventory->last_restock_date = now();
                    $destinationInventory->save();
                }
            }
        });

        // Show success notification
        Notification::make()
            ->success()
            ->title('Movimiento registrado')
            ->body('El inventario ha sido actualizado correctamente.')
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

