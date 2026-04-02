<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Api\DiscountRuleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AuthController;

use Illuminate\Support\Facades\Route;

Route::resource('/products',ProductController::class);

Route::get('/products/{category}/{subcategory?}', [ProductController::class, 'index']);

Route::resource('/categories',CategoryController::class);

Route::resource('/attributes',AttributeController::class);

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/discount-rules', [DiscountRuleController::class, 'index']);

// Wompi calls this — no auth, signature verified inside the controller
Route::post('/webhooks/wompi', [PaymentController::class, 'handleWebhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/checkout', [CartController::class, 'checkout']);
    Route::get('/orders', [CartController::class, 'orders']);
    Route::get('/orders/{order}', [CartController::class, 'showOrder']);
});
