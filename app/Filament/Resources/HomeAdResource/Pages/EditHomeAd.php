<?php

namespace App\Filament\Resources\HomeAdResource\Pages;

use App\Filament\Resources\HomeAdResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHomeAd extends EditRecord
{
    protected static string $resource = HomeAdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
