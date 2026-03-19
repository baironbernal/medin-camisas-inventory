<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Información del Pedido')
                    ->schema([
                        TextEntry::make('order_number')->label('Número de Pedido'),
                        TextEntry::make('status')
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
                        TextEntry::make('customer_name')->label('Cliente'),
                        TextEntry::make('customer_email')->label('Email'),
                        TextEntry::make('customer_phone')->label('Teléfono'),
                        TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),

                Section::make('Dirección de Envío')
                    ->schema([
                        KeyValueEntry::make('shipping_address')
                            ->label('Dirección'),
                    ]),

                Section::make('Items del Pedido')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                TextEntry::make('product_name')->label('Producto'),
                                TextEntry::make('variant_sku')->label('SKU'),
                                TextEntry::make('quantity')->label('Cantidad'),
                                TextEntry::make('unit_price')->label('Precio Unitario')->money('COP'),
                                TextEntry::make('discounted_unit_price')->label('Precio con Descuento')->money('COP'),
                                TextEntry::make('discount_percentage')->label('Descuento %'),
                                TextEntry::make('total_price')->label('Total')->money('COP'),
                                TextEntry::make('discounted_total_price')->label('Total con Descuento')->money('COP'),
                            ])
                            ->columns(5),
                    ]),

                Section::make('Totales')
                    ->schema([
                        TextEntry::make('subtotal_original')->label('Subtotal original')->money('COP'),
                        TextEntry::make('subtotal_discounted')->label('Subtotal con descuento')->money('COP'),
                        TextEntry::make('tax')->label('Impuesto')->money('COP'),
                        TextEntry::make('shipping_cost')->label('Costo de Envío')->money('COP'),
                        TextEntry::make('total')->label('Total')->money('COP'),
                    ])
                    ->columns(4),

                Section::make('Notas')
                    ->schema([
                        TextEntry::make('notes')->label('Notas'),
                    ]),
            ]);
    }
}
