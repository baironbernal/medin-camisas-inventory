<?php

namespace App\Http\Controllers;

use App\Models\Metadato;
use App\Models\Order;
use Illuminate\Support\Facades\View;

class OrderPrintController extends Controller
{
    public function printPackingSlip(Order $order)
    {
        $order->load(['items.productVariant.variantAttributes.attribute', 'items.productVariant.variantAttributes.attributeValue']);
        $preparedBy = auth()->user();
        $company = Metadato::siteInfo();

        return View::make('orders.packing-slip', compact('order', 'preparedBy', 'company'));
    }
}
