<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountRuleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\DiscountRule;

class DiscountRuleResource extends Resource
{
    protected static ?string $model = \App\Models\DiscountRule::class;

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Descuentos';

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('min_quantity')
                ->numeric()
                ->required()
                ->minValue(1),

            Forms\Components\TextInput::make('max_quantity')
                ->numeric()
                ->nullable()
                ->helperText('Leave empty for unlimited (12+)'),

            Forms\Components\TextInput::make('discount_value')
                ->numeric()
                ->required()
                ->suffix('%')
                ->minValue(0)
                ->maxValue(100),

            Forms\Components\Toggle::make('is_active')
                ->default(true),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name'),

                Tables\Columns\TextColumn::make('range')
                    ->label('Quantity Range')
                    ->formatStateUsing(fn ($record) =>
                        $record->max_quantity
                            ? "{$record->min_quantity} - {$record->max_quantity}"
                            : "{$record->min_quantity}+"
                    ),

                Tables\Columns\TextColumn::make('discount_value')
                    ->suffix('%'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

            ])
            ->defaultSort('min_quantity');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDiscountRules::route('/'),
            'create' => Pages\CreateDiscountRule::route('/create'),
            'edit'   => Pages\EditDiscountRule::route('/{record}/edit'),
        ];
    }
}


