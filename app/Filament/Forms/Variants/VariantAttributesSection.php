<?php

namespace App\Filament\Forms\Variants;

use App\Models\Attribute;
use App\Support\VariantHelper;
use Filament\Forms;
use Filament\Forms\Components\Section;

class VariantAttributesSection
{
    public static function make(): Section
    {
        return Section::make('Selecciona los Atributos y Valores')
            ->description('Selecciona las combinaciones que deseas crear.')
            ->schema([
                // ── Sizes ───────────────────────────────────────────────────
                Forms\Components\Toggle::make('full_curve')
                    ->label('Curva completa (todas las tallas)')
                    ->reactive()
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        if ($state) {
                            $sizeAttr = Attribute::size()->first();
                            if ($sizeAttr) {
                                $set('sizes', $sizeAttr->values()
                                    ->where('is_active', true)
                                    ->pluck('id')
                                    ->toArray());
                            }
                        }
                    }),

                Forms\Components\CheckboxList::make('sizes')
                    ->label('Tallas')
                    ->options(fn () => Attribute::size()->first()?->values()
                        ->where('is_active', true)
                        ->pluck('value', 'id')
                        ->toArray() ?? [])
                    ->columns(4)
                    ->reactive()
                    ->hidden(fn (Forms\Get $get) => $get('full_curve')),

                // ── Colors ──────────────────────────────────────────────────
                Forms\Components\Radio::make('colors')
                    ->label('Color')
                    ->options(fn () => Attribute::color()->first()?->values()
                        ->where('is_active', true)
                        ->pluck('value', 'id')
                        ->toArray() ?? [])
                    ->reactive()
                    ->required(fn (Forms\Get $get) => ! $get('other_color'))
                    ->hidden(fn (Forms\Get $get) => $get('other_color')),

                Forms\Components\Toggle::make('other_color')
                    ->label('Otro color (especificar)')
                    ->reactive(),

                Forms\Components\TextInput::make('other_color_value')
                    ->label('Nombre del Color')
                    ->visible(fn (Forms\Get $get) => $get('other_color'))
                    ->required(fn (Forms\Get $get) => $get('other_color'))
                    ->reactive(),

                Forms\Components\ColorPicker::make('other_color_hex')
                    ->label('Color (Hexadecimal)')
                    ->visible(fn (Forms\Get $get) => $get('other_color'))
                    ->required(fn (Forms\Get $get) => $get('other_color'))
                    ->hex()
                    ->reactive(),

                // ── Materials ───────────────────────────────────────────────
                Forms\Components\Radio::make('materials')
                    ->label('Material')
                    ->options(fn () => Attribute::material()->first()?->values()
                        ->where('is_active', true)
                        ->pluck('value', 'id')
                        ->toArray() ?? [])
                    ->reactive()
                    ->required(fn (Forms\Get $get) => ! $get('other_material'))
                    ->hidden(fn (Forms\Get $get) => $get('other_material')),

                Forms\Components\Toggle::make('other_material')
                    ->label('Otro material (especificar)')
                    ->reactive(),

                Forms\Components\TextInput::make('other_material_value')
                    ->label('Nuevo Material')
                    ->visible(fn (Forms\Get $get) => $get('other_material'))
                    ->required(fn (Forms\Get $get) => $get('other_material'))
                    ->reactive(),

                // ── Readiness indicator ─────────────────────────────────────
                Forms\Components\Placeholder::make('show_inventory_indicator')
                    ->label('')
                    ->content(fn (Forms\Get $get) => VariantHelper::shouldShowInventory($get)
                        ? '✓ Se mostrará la sección de inventario'
                        : '')
                    ->visible(fn (Forms\Get $get) => VariantHelper::shouldShowInventory($get)),
            ]);
    }
}
