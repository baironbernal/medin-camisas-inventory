<?php

namespace App\Filament\Forms\Variants;

use Filament\Forms;
use Filament\Forms\Components\Section;

class VariantPricingSection
{
    public static function make(): Section
    {
        return Section::make('Configuración de Precio')
            ->schema([
                Forms\Components\Toggle::make('use_base_price')
                    ->label('Usar precio base del producto')
                    ->default(true)
                    ->reactive(),

                Forms\Components\TextInput::make('custom_price')
                    ->label('Precio personalizado')
                    ->numeric()
                    ->prefix('$')
                    ->visible(fn (Forms\Get $get) => ! $get('use_base_price')),
            ]);
    }
}
