<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // pending → confirmed (descuenta inventario vía OrderObserver)
            Actions\Action::make('confirm_order')
                ->label('Confirmar Pedido')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('¿Confirmar el pedido?')
                ->modalDescription('El pedido quedará confirmado y el inventario se descontará automáticamente. Esta acción no se puede revertir.')
                ->modalSubmitActionLabel('Sí, confirmar')
                ->visible(fn (Order $record): bool => $record->status === Order::STATUS_PENDING)
                ->action(function (Order $record): void {
                    $record->update(['status' => Order::STATUS_CONFIRMED]);
                    Notification::make()->title('Pedido confirmado — inventario descontado')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            // pending | confirmed → cancelled
            Actions\Action::make('cancel_order')
                ->label('Cancelar Pedido')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('¿Cancelar el pedido?')
                ->modalDescription('El pedido se cancelará. El inventario no se verá afectado.')
                ->visible(fn (Order $record): bool => $record->canBeCancelled())
                ->action(function (Order $record): void {
                    $record->update(['status' => Order::STATUS_CANCELLED]);
                    Notification::make()->title('Pedido cancelado')->warning()->send();
                    $this->refreshFormData(['status']);
                }),

            Actions\EditAction::make()->label('Editar'),

            Actions\Action::make('imprimir')
                ->label('Imprimir Orden')
                ->icon('heroicon-o-printer')
                ->url(fn ($record) => route('orders.print-packing-slip', $record))
                ->openUrlInNewTab(),
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
                                'confirmed' => 'success',
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
