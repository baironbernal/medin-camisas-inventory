<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Movement;
use App\Models\Order;
use App\Models\ProductVariant;

class InventoryService {


    /**
     * Deduct stock for every item in a confirmed order and create sale movements.
     * Called after Wompi confirms payment.
     */
    public function deductStockForOrder(Order $order): void
    {
        $order->loadMissing(['items.productVariant.inventories']);

        foreach ($order->items as $item) {
            $variant   = $item->productVariant;
            $inventory = $variant->inventories->first();

            if (! $inventory) {
                throw new \Exception("No hay inventario para el producto {$variant->sku}");
            }

            $before = $inventory->quantity_available;
            $after  = max(0, $before - $item->quantity);

            $inventory->decrement('quantity_available', $item->quantity);

            Movement::create([
                'type'               => Movement::TYPE_SALE,
                'product_variant_id' => $variant->id,
                'inventory_id'       => $inventory->id,
                'store_id'           => $inventory->store_id,
                'quantity'           => $item->quantity,
                'quantity_before'    => $before,
                'quantity_after'     => $after,
                'unit_cost'          => $item->discounted_unit_price,
                'total_cost'         => $item->discounted_total_price,
                'reference_document' => $order->order_number,
                'user_id'            => null,
                'notes'              => "Venta — Orden {$order->order_number}",
                'metadata'           => ['order_id' => $order->id],
            ]);
        }
    }
}