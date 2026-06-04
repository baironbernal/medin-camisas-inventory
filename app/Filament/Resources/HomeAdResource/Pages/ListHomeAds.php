<?php

namespace App\Filament\Resources\HomeAdResource\Pages;

use App\Filament\Resources\HomeAdResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomeAds extends ListRecords
{
    protected static string $resource = HomeAdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
