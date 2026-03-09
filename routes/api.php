<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::resource('/products',ProductController::class);

Route::get('/products/{category}/{subcategory?}', [ProductController::class, 'index']);

Route::resource('/categories',CategoryController::class);

Route::resource('/attributes',AttributeController::class);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/checkout', [CartController::class, 'checkout']);
    Route::get('/orders', [CartController::class, 'orders']);
    Route::get('/orders/{order}', [CartController::class, 'showOrder']);
});
