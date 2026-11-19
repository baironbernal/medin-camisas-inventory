<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\InventoryService;
use App\Services\MetaConversionsService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /**
     * Stamp paid_at the moment the status says the money was received
     * (confirmed for Wompi/transfer/cash, completed for cash on delivery),
     * whatever changed it: Wompi webhook, admin action or the edit form.
     */
    public function saving(Order $order): void
    {
        if ($order->paid_at === null && $order->statusImpliesPaid()) {
            $order->paid_at = now();
        }
    }

    public function updated(Order $order): void
    {
        $this->deductStockOnConfirmation($order);
        $this->reportPurchaseOnPayment($order);
    }

    /**
     * Deduct inventory and register sale movements the moment an order
     * transitions to "confirmed" — whether from the admin panel or from Wompi.
     */
    protected function deductStockOnConfirmation(Order $order): void
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

    /**
     * Report the sale to Meta (Purchase) once the order is actually paid,
     * regardless of the payment method. Sent after the response so a slow
     * Meta API never delays the webhook or the admin panel.
     */
    protected function reportPurchaseOnPayment(Order $order): void
    {
        if (! $order->wasChanged('paid_at') || ! $order->isPaid()) {
            return;
        }

        if (! $order->isFromWebsite() || $order->meta_purchase_sent_at !== null) {
            return;
        }

        $orderId = $order->id;

        dispatch(fn () => app(MetaConversionsService::class)->sendPurchase(Order::find($orderId)))
            ->afterResponse();
    }
}
