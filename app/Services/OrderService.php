<?php

namespace App\Services;


use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;

class OrderService
{
    /**
     * Create an order directly from a list of items sent by the frontend.
     *
     * @param  array  $items  [['product_variant_id' => int, 'quantity' => int], ...]
     * @param  array  $customerData  ['customer_name', 'customer_email', 'customer_phone', 'notes']
     */
    public function createOrderFromItems(array $items, array $customerData, ?int $userId = null): Order
    {
        if (empty($items)) {
            throw new \Exception('El carrito está vacío.');
        }

        // ── 1. Load variants with inventories and validate stock ──────────────
        $variantIds = array_column($items, 'product_variant_id');
        $variants = ProductVariant::with(['product', 'inventories'])
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        $subtotal_original = 0;
        $subtotal_discounted = 0;
        $lineItems = [];

        foreach ($items as $item) {
            $variantId = $item['product_variant_id'];
            $quantity = (int) $item['quantity'];

            $variant = $variants->get($variantId);

            if (! $variant) {
                throw new \Exception("Producto con ID {$variantId} no encontrado.");
            }

            if (! $variant->is_active) {
                throw new \Exception("El producto '{$variant->sku}' ya no está disponible.");
            }

            $availableStock = $variant->total_stock;
            if ($availableStock < $quantity) {
                throw new \Exception(
                    "Stock insuficiente para '{$variant->sku}'. Disponible: {$availableStock}, solicitado: {$quantity}."
                );
            }

            // The frontend can send both original and discounted pricing.
            // If any of them is missing, we fallback to the calculated original price.
            $unitPriceOriginal = isset($item['unit_price']) && $item['unit_price'] !== null
                ? (float) $item['unit_price']
                : (float) $variant->calculatePrice();

            $totalPriceOriginal = isset($item['total_price']) && $item['total_price'] !== null
                ? (float) $item['total_price']
                : ($unitPriceOriginal * $quantity);

            $discountRuleId = $item['discount_rule_id'] ?? null;
            $discountPercentage = $item['discount_percentage'] ?? 0;

            $unitPriceDiscounted = isset($item['discounted_unit_price']) && $item['discounted_unit_price'] !== null
                ? (float) $item['discounted_unit_price']
                : (
                    is_numeric($discountPercentage)
                        ? ($unitPriceOriginal * (1 - ((float) $discountPercentage / 100)))
                        : $unitPriceOriginal
                );

            $totalPriceDiscounted = isset($item['discounted_total_price']) && $item['discounted_total_price'] !== null
                ? (float) $item['discounted_total_price']
                : ($unitPriceDiscounted * $quantity);

            $subtotal_original += $totalPriceOriginal;
            $subtotal_discounted += $totalPriceDiscounted;

            $lineItems[] = [
                'variant' => $variant,
                'quantity' => $quantity,
                'unit_price_original' => $unitPriceOriginal,
                'total_price_original' => $totalPriceOriginal,
                'discount_rule_id' => $discountRuleId,
                'discount_percentage' => $discountPercentage,
                'unit_price_discounted' => $unitPriceDiscounted,
                'total_price_discounted' => $totalPriceDiscounted,
            ];
        }

        // ── 2. Create the Order ───────────────────────────────────────────────
        $order = Order::create([
            'user_id' => $userId,
            'order_number' => Order::generateOrderNumber(),
            'status' => Order::STATUS_PENDING,
            'subtotal_original' => round($subtotal_original, 2),
            'subtotal_discounted' => round($subtotal_discounted, 2),
            'subtotal' => round($subtotal_discounted, 2),
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => round($subtotal_discounted, 2),
            'currency' => 'COP',
            'customer_email' => $customerData['customer_email'] ?? null,
            'customer_name' => $customerData['customer_name'] ?? null,
            'customer_phone' => $customerData['customer_phone'] ?? null,
            'shipping_address' => $customerData['shipping_address'] ?? null,
            'notes' => $customerData['notes'] ?? null,
        ]);

        // ── 3. Create OrderItems ───────────────────────────────────────────────
        foreach ($lineItems as $line) {
            /** @var ProductVariant $variant */
            $variant = $line['variant'];
            $quantity = $line['quantity'];
            $unitPriceOriginal = $line['unit_price_original'];
            $totalPriceOriginal = $line['total_price_original'];
            $discountRuleId = $line['discount_rule_id'];
            $discountPercentage = $line['discount_percentage'];
            $unitPriceDiscounted = $line['unit_price_discounted'];
            $totalPriceDiscounted = $line['total_price_discounted'];

            OrderItem::create([
                'order_id' => $order->id,
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_sku' => $variant->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPriceOriginal,
                'total_price' => $totalPriceOriginal,
                'discount_rule_id' => $discountRuleId,
                'discount_percentage' => $discountPercentage,
                'discounted_unit_price' => $unitPriceDiscounted,
                'discounted_total_price' => $totalPriceDiscounted,
            ]);
        }

        return $order->load(['items.productVariant']);
    }
}
