<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Infolists;
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
                Infolists\Section::make('Información del Pedido')
                    ->schema([
                        Infolists\Entry::make('order_number')->label('Número de Pedido'),
                        Infolists\Entry::make('status')
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
                        Infolists\Entry::make('customer_name')->label('Cliente'),
                        Infolists\Entry::make('customer_email')->label('Email'),
                        Infolists\Entry::make('customer_phone')->label('Teléfono'),
                        Infolists\Entry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),

                Infolists\Section::make('Dirección de Envío')
                    ->schema([
                        Infolists\Entry::make('shipping_address')
                            ->label('Dirección')
                            ->keyValue(),
                    ]),

                Infolists\Section::make('Items del Pedido')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                Infolists\Entry::make('product_name')->label('Producto'),
                                Infolists\Entry::make('variant_sku')->label('SKU'),
                                Infolists\Entry::make('quantity')->label('Cantidad'),
                                Infolists\Entry::make('unit_price')->label('Precio Unitario')->money('COP'),
                                Infolists\Entry::make('total_price')->label('Total')->money('COP'),
                            ])
                            ->columns(5),
                    ]),

                Infolists\Section::make('Totales')
                    ->schema([
                        Infolists\Entry::make('subtotal')->label('Subtotal')->money('COP'),
                        Infolists\Entry::make('tax')->label('Impuesto')->money('COP'),
                        Infolists\Entry::make('shipping_cost')->label('Costo de Envío')->money('COP'),
                        Infolists\Entry::make('total')->label('Total')->money('COP'),
                    ])
                    ->columns(4),

                Infolists\Section::make('Notas')
                    ->schema([
                        Infolists\Entry::make('notes')->label('Notas'),
                    ]),
            ]);
    }
}
