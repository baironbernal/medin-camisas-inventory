<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Movement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;

class CartService
{
    /**
     * Create an order directly from a list of items sent by the frontend.
     *
     * @param  array  $items        [['product_variant_id' => int, 'quantity' => int], ...]
     * @param  array  $customerData ['customer_name', 'customer_email', 'customer_phone', 'notes']
     * @param  int|null $userId
     * @return Order
     */
    public function createOrderFromItems(array $items, array $customerData, ?int $userId = null): Order
    {
        if (empty($items)) {
            throw new \Exception('El carrito está vacío.');
        }

        // ── 1. Load variants with inventories and validate stock ──────────────
        $variantIds = array_column($items, 'product_variant_id');
        $variants   = ProductVariant::with(['product', 'inventories'])
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        $subtotal = 0;
        $lineItems = [];

        foreach ($items as $item) {
            $variantId = $item['product_variant_id'];
            $quantity  = (int) $item['quantity'];

            $variant = $variants->get($variantId);

            if (!$variant) {
                throw new \Exception("Producto con ID {$variantId} no encontrado.");
            }

            if (!$variant->is_active) {
                throw new \Exception("El producto '{$variant->sku}' ya no está disponible.");
            }

            $availableStock = $variant->total_stock;
            if ($availableStock < $quantity) {
                throw new \Exception(
                    "Stock insuficiente para '{$variant->sku}'. Disponible: {$availableStock}, solicitado: {$quantity}."
                );
            }

            $price     = $variant->calculatePrice();
            $subtotal += $price * $quantity;

            $lineItems[] = [
                'variant'   => $variant,
                'quantity'  => $quantity,
                'price'     => $price,
            ];
        }

        // ── 2. Create the Order ───────────────────────────────────────────────
        $order = Order::create([
            'user_id'        => $userId,
            'order_number'   => Order::generateOrderNumber(),
            'status'         => Order::STATUS_PENDING,
            'subtotal'       => round($subtotal, 2),
            'tax'            => 0,
            'shipping_cost'  => 0,
            'total'          => round($subtotal, 2),
            'currency'       => 'COP',
            'customer_email' => $customerData['customer_email'] ?? null,
            'customer_name'  => $customerData['customer_name']  ?? null,
            'customer_phone' => $customerData['customer_phone'] ?? null,
            'notes'          => $customerData['notes']          ?? null,
        ]);

        // ── 3. Create OrderItems + deduct Inventory + register Movements ──────
        foreach ($lineItems as $line) {
            /** @var ProductVariant $variant */
            $variant  = $line['variant'];
            $quantity = $line['quantity'];
            $price    = $line['price'];

            OrderItem::create([
                'order_id'           => $order->id,
                'product_variant_id' => $variant->id,
                'product_name'       => $variant->product->name,
                'variant_sku'        => $variant->sku,
                'quantity'           => $quantity,
                'unit_price'         => $price,
                'total_price'        => $price * $quantity,
            ]);

            // Deduct stock from the first inventory record for this variant
            $inventory = $variant->inventories->first();
            if ($inventory) {
                $before = $inventory->quantity_available;
                $after  = max(0, $before - $quantity);

                $inventory->decrement('quantity_available', $quantity);

                Movement::create([
                    'type'               => Movement::TYPE_SALE,
                    'product_variant_id' => $variant->id,
                    'inventory_id'       => $inventory->id,
                    'store_id'           => $inventory->store_id,
                    'quantity'           => $quantity,
                    'quantity_before'    => $before,
                    'quantity_after'     => $after,
                    'unit_cost'          => $price,
                    'total_cost'         => $price * $quantity,
                    'reference_document' => $order->order_number,
                    'user_id'            => $userId,
                    'notes'              => "Venta — Orden {$order->order_number}",
                    'metadata'           => ['order_id' => $order->id],
                ]);
            }
        }

        return $order->load(['items.productVariant']);
    }
}
