<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Gestión de Pedidos';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Pedidos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Pedido')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Número de Pedido')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'confirmed' => 'Confirmado',
                                'processing' => 'Procesando',
                                'completed' => 'Completado',
                                'cancelled' => 'Cancelado',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Nombre del Cliente')
                            ->disabled(),
                        Forms\Components\TextInput::make('customer_email')
                            ->label('Email')
                            ->disabled(),
                        Forms\Components\TextInput::make('customer_phone')
                            ->label('Teléfono')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Dirección de Envío')
                    ->schema([
                        Forms\Components\KeyValue::make('shipping_address')
                            ->label('Dirección')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Totales')
                    ->schema([
                        Forms\Components\TextInput::make('subtotal_original')
                            ->label('Subtotal original')
                            ->disabled()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('subtotal_discounted')
                            ->label('Subtotal con descuento')
                            ->disabled()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('tax')
                            ->label('Impuesto')
                            ->disabled()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('shipping_cost')
                            ->label('Costo de Envío')
                            ->disabled()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('total')
                            ->label('Total')
                            ->disabled()
                            ->prefix('$'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Número de Pedido')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('COP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'processing' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'processing' => 'Procesando',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'confirmed' => 'Confirmado',
                        'processing' => 'Procesando',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->label('Fecha')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn ($query) => $query->whereDate('created_at', '>=', $data['created_from'])
                            )
                            ->when(
                                $data['created_until'],
                                fn ($query) => $query->whereDate('created_at', '<=', $data['created_until'])
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
