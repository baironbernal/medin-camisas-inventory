<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InventoryService;
use App\Services\WompiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected WompiService $wompiService,
        protected InventoryService $inventoryService,
    ) {}

    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        // ── 1. Verify the event signature ─────────────────────────────────────
        if (! $this->wompiService->verifyWebhookEvent($payload)) {
            Log::warning('Wompi webhook: invalid signature', ['payload' => $payload]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // ── 2. Only handle transaction updates ────────────────────────────────
        if (($payload['event'] ?? '') !== 'transaction.updated') {
            return response()->json(['message' => 'Event ignored'], 200);
        }

        $transaction = $payload['data']['transaction'] ?? null;
        if (! $transaction) {
            return response()->json(['message' => 'No transaction data'], 422);
        }

        $reference     = $transaction['reference']  ?? null;
        $status        = $transaction['status']     ?? null;
        $transactionId = $transaction['id']         ?? null;

        // ── 3. Find the order by its order_number (= Wompi reference) ─────────
        $order = Order::where('order_number', $reference)->first();

        if (! $order) {
            Log::warning('Wompi webhook: order not found', ['reference' => $reference]);
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Idempotency: skip if order is already past pending
        if ($order->status !== Order::STATUS_PENDING) {
            return response()->json(['message' => 'Order already processed'], 200);
        }

        // ── 4. Act on payment status ──────────────────────────────────────────
        try {
            DB::transaction(function () use ($order, $status, $transactionId) {
                if ($status === 'APPROVED') {
                    $order->update([
                        'status'               => Order::STATUS_CONFIRMED,
                        'wompi_transaction_id' => $transactionId,
                    ]);

                    $this->inventoryService->deductStockForOrder($order);

                } elseif (in_array($status, ['DECLINED', 'VOIDED', 'ERROR'])) {
                    $order->update([
                        'status'               => Order::STATUS_CANCELLED,
                        'wompi_transaction_id' => $transactionId,
                    ]);
                }
                // PENDING status → do nothing, wait for next event
            });
        } catch (\Throwable $e) {
            Log::error('Wompi webhook: failed to process payment', [
                'order'     => $order->order_number,
                'status'    => $status,
                'exception' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Internal error'], 500);
        }

        return response()->json(['message' => 'OK'], 200);
    }
}
