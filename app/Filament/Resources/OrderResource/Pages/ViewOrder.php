<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\KeyValueEntry;
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
            Actions\Action::make('confirm_order')
                ->label('Confirmar Pedido')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->modalHeading('Confirmar pedido')
                ->modalDescription('Sube el comprobante de pago antes de confirmar. El inventario se descontará automáticamente.')
                ->modalSubmitActionLabel('Confirmar y guardar comprobante')
                ->visible(fn (Order $record): bool => $record->status === Order::STATUS_PENDING)
                ->form([
                    FileUpload::make('payment_proof_path')
                        ->label('Comprobante de pago')
                        ->image()
                        ->disk('public')
                        ->directory('payment-proofs')
                        ->imagePreviewHeight('200')
                        ->required(),
                ])
                ->action(function (Order $record, array $data): void {
                    $record->update([
                        'status'             => Order::STATUS_CONFIRMED,
                        'payment_proof_path' => $data['payment_proof_path'],
                    ]);
                    Notification::make()->title('Pedido confirmado — inventario descontado')->success()->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $record->id]));
                }),

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
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $record->id]));
                }),

            Actions\Action::make('assign_wholesaler')
                ->label(fn (): string => $this->record->user_id ? 'Cambiar Mayorista' : 'Asignar Mayorista')
                ->icon('heroicon-o-user-plus')
                ->color('info')
                ->modalHeading('Asociar Mayorista al Pedido')
                ->modalWidth('md')
                ->form([
                    Select::make('user_id')
                        ->label('Mayorista')
                        ->options(fn () => User::role('wholesaler')
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn (User $u) => [
                                $u->id => "{$u->full_name} — {$u->phone_number}",
                            ])
                            ->toArray()
                        )
                        ->placeholder('Sin mayorista')
                        ->searchable()
                        ->nullable(),
                ])
                ->mountUsing(fn (\Filament\Forms\Form $form) => $form->fill([
                    'user_id' => $this->record->user_id,
                ]))
                ->action(function (array $data): void {
                    $this->record->update(['user_id' => $data['user_id']]);
                    $label = $data['user_id']
                        ? User::find($data['user_id'])?->full_name
                        : null;
                    Notification::make()
                        ->title($label ? "Mayorista asignado: {$label}" : 'Mayorista desvinculado')
                        ->success()
                        ->send();
                    $this->refreshFormData(['user_id']);
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
                                'pending'    => 'warning',
                                'confirmed'  => 'success',
                                'processing' => 'primary',
                                'completed'  => 'success',
                                'cancelled'  => 'danger',
                                default      => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'pending'    => 'Pendiente',
                                'confirmed'  => 'Confirmado',
                                'processing' => 'Procesando',
                                'completed'  => 'Completado',
                                'cancelled'  => 'Cancelado',
                                default      => $state,
                            }),
                        TextEntry::make('customer_name')->label('Cliente'),
                        TextEntry::make('customer_email')->label('Email'),
                        TextEntry::make('customer_phone')->label('Teléfono'),
                        TextEntry::make('user.full_name')
                            ->label('Mayorista')
                            ->placeholder('Sin asignar')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),

                Section::make('Dirección de Envío')
                    ->schema([
                        KeyValueEntry::make('shipping_address')->label('Dirección'),
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
