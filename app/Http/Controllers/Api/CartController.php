<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\WompiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected WompiService $wompiService,
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'customer_email' => 'required|email',
            'customer_name'  => 'required|string',
            'customer_phone' => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.unit_price'         => 'nullable|numeric|min:0',
            'items.*.total_price'        => 'nullable|numeric|min:0',
            'items.*.discount_rule_id'  => 'nullable|exists:discount_rules,id',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.discounted_unit_price' => 'nullable|numeric|min:0',
            'items.*.discounted_total_price' => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
        ]);

        $userId = Auth::id();

        try {
            $customerData = [
                'customer_email' => $request->customer_email,
                'customer_name'  => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'notes'          => $request->notes,
            ];

            $orderWithItems = $this->orderService->createOrderFromItems(
                $request->items,
                $customerData,
                $userId
            );

            $amountInCents = (int) round($orderWithItems->total * 100);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado correctamente.',
                'data' => [
                    'order' => [
                        'id'                  => $orderWithItems->id,
                        'order_number'        => $orderWithItems->order_number,
                        'status'              => $orderWithItems->status,
                        'subtotal_original'   => $orderWithItems->subtotal_original,
                        'subtotal_discounted' => $orderWithItems->subtotal_discounted,
                        'total'               => $orderWithItems->total,
                        'currency'            => $orderWithItems->currency,
                        'created_at'          => $orderWithItems->created_at->toIso8601String(),
                    ],
                    'items' => $orderWithItems->items->map(fn ($item) => [
                        'product_name'           => $item->product_name,
                        'variant_sku'            => $item->variant_sku,
                        'quantity'               => $item->quantity,
                        'unit_price'             => $item->unit_price,
                        'total_price'            => $item->total_price,
                        'discount_rule_id'       => $item->discount_rule_id,
                        'discount_percentage'    => $item->discount_percentage,
                        'discounted_unit_price'  => $item->discounted_unit_price,
                        'discounted_total_price' => $item->discounted_total_price,
                    ]),
                    'payment' => $this->wompiService->widgetConfig(
                        $orderWithItems->order_number,
                        $amountInCents,
                        $orderWithItems->currency,
                    ),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function orders(Request $request): JsonResponse
    {
        $userId = Auth::id();

        $orders = Order::when($userId, fn ($q) => $q->forUser($userId))
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    public function showOrder(Order $order): JsonResponse
    {
        // ... (Make sure order belongs to user if we want security in the future)
        $order->load(['items.productVariant']);

        return response()->json([
            'success' => true,
            'data' => [
                'id'               => $order->id,
                'order_number'     => $order->order_number,
                'status'           => $order->status,
                'subtotal_original' => $order->subtotal_original,
                'subtotal_discounted' => $order->subtotal_discounted,
                'subtotal'         => $order->subtotal,
                'tax'              => $order->tax,
                'shipping_cost'    => $order->shipping_cost,
                'total'            => $order->total,
                'currency'         => $order->currency,
                'customer_email'   => $order->customer_email,
                'customer_name'    => $order->customer_name,
                'customer_phone'   => $order->customer_phone,
                'shipping_address' => $order->shipping_address,
                'notes'            => $order->notes,
                'created_at'       => $order->created_at->toIso8601String(),
                'items' => $order->items->map(fn ($item) => [
                    'id'                 => $item->id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name'       => $item->product_name,
                    'variant_sku'        => $item->variant_sku,
                    'quantity'           => $item->quantity,
                    'unit_price'         => $item->unit_price,
                    'total_price'        => $item->total_price,
                    'discount_rule_id'  => $item->discount_rule_id,
                    'discount_percentage' => $item->discount_percentage,
                    'discounted_unit_price' => $item->discounted_unit_price,
                    'discounted_total_price' => $item->discounted_total_price,
                ]),
            ],
        ]);
    }
}
