<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Descuentos';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Descuentos manuales';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('description')
                ->label('Descripción')
                ->nullable()
                ->rows(2),

            Forms\Components\Select::make('type')
                ->label('Tipo')
                ->options([
                    'percentage' => 'Porcentaje (%)',
                    'fixed'      => 'Valor fijo (COP)',
                ])
                ->required()
                ->default('percentage')
                ->live(),

            Forms\Components\TextInput::make('value')
                ->label('Valor')
                ->numeric()
                ->required()
                ->minValue(0)
                ->suffix(fn (Forms\Get $get) => $get('type') === 'percentage' ? '%' : 'COP')
                ->helperText('Porcentaje: 10 = 10% de descuento. Fijo: 5000 = $5.000 COP de descuento.'),

            Forms\Components\Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'percentage' ? 'Porcentaje' : 'Valor fijo')
                    ->color(fn (string $state): string => $state === 'percentage' ? 'info' : 'warning'),

                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->formatStateUsing(
                        fn ($state, Discount $record): string => $record->type === 'percentage'
                            ? "{$state}%"
                            : '$' . number_format((float) $state, 0, ',', '.')
                    ),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Activo'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit'   => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
