<?php

use App\Http\Controllers\OrderPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/orders/{order}/print', [OrderPrintController::class, 'printPackingSlip'])
    ->name('orders.print-packing-slip');
