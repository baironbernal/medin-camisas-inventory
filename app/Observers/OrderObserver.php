<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /**
     * Deduct inventory and register sale movements the moment an order
     * transitions to "confirmed" — whether from the admin panel or from Wompi.
     */
    public function updated(Order $order): void
    {
        $statusChanged = $order->wasChanged('status');
        $isNowConfirmed = $order->status === Order::STATUS_CONFIRMED;
        $wasPreviouslyConfirmed = $order->getOriginal('status') === Order::STATUS_CONFIRMED;

        if (! $statusChanged || ! $isNowConfirmed || $wasPreviouslyConfirmed) {
            return;
        }

        try {
            app(InventoryService::class)->deductStockForOrder($order);
        } catch (\Exception $e) {
            Log::error("Error deducting stock for order {$order->order_number}: {$e->getMessage()}");
            throw $e; // re-throw so the status update rolls back
        }
    }
}
