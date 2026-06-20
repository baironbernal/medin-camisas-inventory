<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    /** Captured permission selections, stripped from the model payload. */
    protected array $selectedPermissions = [];

    /**
     * Hydrate the grouped checkbox state (`perms.<module>`) from the role's
     * current permissions so the form reflects what's already granted.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $grouped = $this->record->permissions
            ->groupBy('module')
            ->map(fn ($perms) => $perms->pluck('name')->all())
            ->toArray();

        $data['perms'] = $grouped;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->selectedPermissions = collect($data['perms'] ?? [])
            ->flatten()->filter()->unique()->values()->all();
        unset($data['perms']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncPermissions($this->selectedPermissions);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => ! in_array($this->record->name, RoleResource::PROTECTED_ROLES, true)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
