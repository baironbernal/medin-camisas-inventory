<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /** Captured permission selections, stripped from the model payload. */
    protected array $selectedPermissions = [];

    /**
     * Pull the grouped `perms.*` checkbox state out of the data before the
     * Role model is created (it is not a column), flatten it to permission
     * names, and stash it for afterCreate().
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedPermissions = $this->flattenPerms($data['perms'] ?? []);
        unset($data['perms']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncPermissions($this->selectedPermissions);
    }

    /** ['products' => ['products.view',...], ...] => ['products.view', ...] */
    protected function flattenPerms(array $grouped): array
    {
        return collect($grouped)->flatten()->filter()->unique()->values()->all();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
