<?php

namespace App\Observers;

use App\Models\Inventory;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class InventoryObserver
{
    const LOW_STOCK_THRESHOLD = 12;

    /**
     * Fire when quantity_available is updated.
     * Sends a Filament database notification to every admin user
     * if stock just crossed into the ≤12 zone.
     */
    public function updated(Inventory $inventory): void
    {
        if (! $inventory->wasChanged('quantity_available')) {
            return;
        }

        $newQty  = $inventory->quantity_available;
        $prevQty = (int) $inventory->getOriginal('quantity_available');

        // Only notify once when crossing the threshold, not on every update below it
        if ($newQty > self::LOW_STOCK_THRESHOLD || $prevQty <= self::LOW_STOCK_THRESHOLD) {
            return;
        }

        $inventory->loadMissing(['productVariant.product', 'store']);

        $variant  = $inventory->productVariant;
        $product  = $variant?->product;
        $store    = $inventory->store;

        $title = "Stock bajo: {$variant?->sku}";
        $body  = ($product ? $product->name.' — ' : '')
            .($variant?->attributes_text ? $variant->attributes_text.' — ' : '')
            ."Solo quedan {$newQty} unidades"
            .($store ? " en {$store->name}" : '');

        $recipients = User::role(['owner', 'admin'])->get();

        $notification = Notification::make()
            ->title($title)
            ->body($body)
            ->warning()
            ->icon('heroicon-o-exclamation-triangle')
            ->actions([
                Action::make('ver_inventario')
                    ->label('Ver inventario')
                    ->url(route('filament.admin.resources.inventories.index'))
                    ->markAsRead(),
            ]);

        foreach ($recipients as $admin) {
            // notifyNow bypasses ShouldQueue on Filament's DatabaseNotification
            $admin->notifyNow($notification->toDatabase());
        }
    }
}
