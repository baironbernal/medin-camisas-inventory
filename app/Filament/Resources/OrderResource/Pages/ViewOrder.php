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
                        \Filament\Infolists\Components\TextEntry::make('items_table')
                            ->label('')
                            ->html()
                            ->columnSpanFull()
                            ->state(function ($record) {
                                $items = $record->items->load(['productVariant.media']);

                                $rows = '';
                                foreach ($items as $item) {
                                    $imageUrl = $item->productVariant?->getFirstMediaUrl('variant-images') ?? '';
                                    $imgTag = $imageUrl
                                        ? "<img src=\"{$imageUrl}\" style=\"width:48px;height:48px;object-fit:cover;border-radius:4px;\">"
                                        : "<div style=\"width:48px;height:48px;background:#f3f4f6;border-radius:4px;\"></div>";

                                    $subtotal = number_format((float) $item->discounted_total_price, 0, ',', '.');
                                    $unitPrice = number_format((float) $item->discounted_unit_price, 0, ',', '.');
                                    $discount = $item->discount_percentage > 0 ? $item->discount_percentage . '%' : '—';

                                    $rows .= "
                                        <tr style=\"border-bottom:1px solid #f3f4f6;\">
                                            <td style=\"padding:8px 12px;\">{$imgTag}</td>
                                            <td style=\"padding:8px 12px;font-weight:500;\">{$item->product_name}</td>
                                            <td style=\"padding:8px 12px;color:#6b7280;font-size:12px;\">{$item->variant_sku}</td>
                                            <td style=\"padding:8px 12px;text-align:center;\">{$item->quantity}</td>
                                            <td style=\"padding:8px 12px;text-align:right;\">\${$unitPrice}</td>
                                            <td style=\"padding:8px 12px;text-align:center;color:#f59e0b;\">{$discount}</td>
                                            <td style=\"padding:8px 12px;text-align:right;font-weight:600;\">\${$subtotal}</td>
                                        </tr>
                                    ";
                                }

                                return "
                                    <table style=\"width:100%;border-collapse:collapse;font-size:13px;\">
                                        <thead>
                                            <tr style=\"background:#f9fafb;border-bottom:2px solid #e5e7eb;\">
                                                <th style=\"padding:8px 12px;text-align:left;font-weight:600;color:#374151;\">Foto</th>
                                                <th style=\"padding:8px 12px;text-align:left;font-weight:600;color:#374151;\">Producto</th>
                                                <th style=\"padding:8px 12px;text-align:left;font-weight:600;color:#374151;\">SKU</th>
                                                <th style=\"padding:8px 12px;text-align:center;font-weight:600;color:#374151;\">Cant.</th>
                                                <th style=\"padding:8px 12px;text-align:right;font-weight:600;color:#374151;\">Precio Unit.</th>
                                                <th style=\"padding:8px 12px;text-align:center;font-weight:600;color:#374151;\">Dcto.</th>
                                                <th style=\"padding:8px 12px;text-align:right;font-weight:600;color:#374151;\">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>{$rows}</tbody>
                                    </table>
                                ";
                            }),
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
