<?php

namespace App\Filament\Resources\InventoryResource\Pages;

use App\Filament\Resources\InventoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function getFormActions(): array
    {
        return array_merge(
            parent::getFormActions(),
            [
                // Keep the default create and cancel actions
            ]
        );
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values for dates if needed
        $data['last_inventory_check_date'] = $data['last_inventory_check_date'] ?? now();
        
        return $data;
    }
}

