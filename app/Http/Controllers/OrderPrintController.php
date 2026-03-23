<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\View;

class OrderPrintController extends Controller
{
    public function printPackingSlip(Order $order)
    {
        $order->load(['items.productVariant.variantAttributes.attribute', 'items.productVariant.variantAttributes.attributeValue']);

        return View::make('orders.packing-slip', compact('order'));
    }
}
