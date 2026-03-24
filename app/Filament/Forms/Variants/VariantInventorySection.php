<?php

namespace App\Filament\Forms\Variants;

use App\Models\Store;
use App\Support\VariantHelper;
use Filament\Forms;
use Filament\Forms\Components\Section;

class VariantInventorySection
{
    public static function make(): Section
    {
        return Section::make('Inventario')
            ->description('Configure el inventario inicial para cada variante generada')
            ->visible(fn (Forms\Get $get) => VariantHelper::shouldShowInventory($get))
            ->schema([
                Forms\Components\Repeater::make('inventories')
                    ->label('Inventario por Tienda')
                    ->schema([
                        Forms\Components\Select::make('store_id')
                            ->label('Tienda')
                            ->options(fn () => Store::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('quantity_available')
                            ->label('Cantidad Disponible')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\TextInput::make('quantity_reserved')
                            ->label('Cantidad Reservada')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\TextInput::make('quantity_in_transit')
                            ->label('Cantidad en Tránsito')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\TextInput::make('min_quantity')
                            ->label('Cantidad Mínima')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('max_quantity')
                            ->label('Cantidad Máxima')
                            ->numeric()
                            ->minValue(0),
                        Forms\Components\TextInput::make('reorder_point')
                            ->label('Punto de Reorden')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\TextInput::make('location')
                            ->label('Ubicación')
                            ->maxLength(255),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->minItems(1),
            ]);
    }
}
