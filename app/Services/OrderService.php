<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        protected CartService $cartService,
    ) {}

    /**
     * Create a pending order from items submitted by the storefront.
     *
     * The frontend only sends product_variant_id + quantity.
     * All prices are calculated server-side by CartService — the single source of truth.
     *
     * @param  array  $items         [['product_variant_id' => int, 'quantity' => int], ...]
     * @param  array  $customerData
     * @param  int|null  $userId
     */
    public function createOrderFromItems(array $items, array $customerData, ?int $userId = null): Order
    {
        if (empty($items)) {
            throw new BusinessException('El carrito está vacío.');
        }

        // ── 1. Calculate everything server-side ───────────────────────────────
        $calculated = $this->cartService->calculate($items);

        if (empty($calculated['items'])) {
            throw new BusinessException('Ningún producto del carrito está disponible.');
        }

        return DB::transaction(function () use ($items, $calculated, $customerData, $userId) {

        // ── 2. Validate stock — with exclusive row lock ───────────────────────
        // lockForUpdate() acquires an exclusive row-level lock inside this
        // transaction. Any concurrent checkout for the same variants will block
        // here until this transaction commits, eliminating the TOCTOU race.
        $variantIds  = array_column($items, 'product_variant_id');
        $quantityMap = array_column($items, 'quantity', 'product_variant_id');

        $variants = \App\Models\ProductVariant::with('inventories')
            ->whereIn('id', $variantIds)
            ->where('is_active', true)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($variantIds as $variantId) {
            $variant  = $variants->get($variantId);
            $quantity = (int) ($quantityMap[$variantId] ?? 0);

            if (! $variant) {
                throw new BusinessException("Producto con ID {$variantId} no encontrado.");
            }

            if ($variant->total_stock < $quantity) {
                throw new BusinessException(
                    "Stock insuficiente para '{$variant->sku}'. ".
                    "Disponible: {$variant->total_stock}, solicitado: {$quantity}."
                );
            }
        }

        // ── 3. Create the Order record ────────────────────────────────────────
        $order = Order::create([
            'user_id'             => $userId,
            'order_number'        => Order::generateOrderNumber(),
            'status'              => Order::STATUS_PENDING,
            'subtotal_original'   => $calculated['subtotal_original'],
            'subtotal_discounted' => $calculated['subtotal_discounted'],
            'subtotal'            => $calculated['subtotal_discounted'],
            'tax'                 => 0,
            'shipping_cost'       => 0,
            'total'               => $calculated['subtotal_discounted'],
            'currency'            => 'COP',
            'customer_email'      => $customerData['customer_email'] ?? null,
            'customer_name'       => $customerData['customer_name']  ?? null,
            'customer_phone'      => $customerData['customer_phone'] ?? null,
            'shipping_address'    => $customerData['shipping_address'] ?? null,
            'notes'               => $customerData['notes'] ?? null,
        ]);

        // ── 4. Create OrderItems in a single batch INSERT ─────────────────────
        $variantNames = \App\Models\ProductVariant::with('product')
            ->whereIn('id', array_column($calculated['items'], 'product_variant_id'))
            ->get()
            ->keyBy('id');

        $orderItemsData = [];

        foreach ($calculated['items'] as $line) {
            $variant = $variantNames->get($line['product_variant_id']);

            $orderItemsData[] = [
                'order_id'               => $order->id,
                'product_variant_id'     => $line['product_variant_id'],
                'product_name'           => $variant?->product?->name ?? '',
                'variant_sku'            => $variant?->sku ?? '',
                'quantity'               => $line['quantity'],
                'unit_price'             => $line['unit_price'],
                'total_price'            => $line['total_price'],
                'discount_rule_id'       => $line['discount_rule_id'],
                'discount_percentage'    => $line['discount_percentage'],
                'discounted_unit_price'  => $line['discounted_unit_price'],
                'discounted_total_price' => $line['discounted_total_price'],
                'created_at'             => now(),
                'updated_at'             => now(),
            ];
        }

        OrderItem::insert($orderItemsData);

        Log::info('Order created from storefront', [
            'order_number' => $order->order_number,
            'user_id'      => $userId,
            'total'        => $order->total,
            'items'        => count($orderItemsData),
        ]);

        return $order->load(['items.productVariant']);

        }); // end DB::transaction
    }
}
