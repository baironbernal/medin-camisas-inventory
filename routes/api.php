<?php

use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\DiscountRuleController;
use App\Http\Controllers\Api\OrderRulesController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// ── Capa 1: Rutas públicas ─────────────────────────────────────────────────
// Catálogo visible para cualquiera — 60 req/min por IP
Route::middleware('throttle:60,1')->group(function () {
    Route::resource('/products', ProductController::class)->only(['index', 'show']);
    Route::resource('/categories', CategoryController::class)->only(['index', 'show']);
    Route::resource('/attributes', AttributeController::class)->only(['index', 'show']);
    Route::get('/discount-rules', [DiscountRuleController::class, 'index']);
    Route::get('/order-rules', [OrderRulesController::class, 'index']);
});

// ── Capa 2: Autenticación — límite estricto para evitar fuerza bruta ──────
// 5 intentos por minuto por IP
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware(['auth:sanctum', 'throttle:10,1']);

// ── Capa 2b: Cálculo del carrito — público, sin auth ──────────────────────
// No expone datos sensibles; devuelve precios calculados server-side.
Route::middleware('throttle:120,1')->group(function () {
    Route::post('/cart/calculate', [CartController::class, 'calculate']);
});

// ── Capa 3: Webhook de Wompi ───────────────────────────────────────────────
// Sin auth (Wompi no manda token), pero con throttle y firma verificada internamente
Route::middleware('throttle:30,1')->group(function () {
    Route::post('/webhooks/wompi', [PaymentController::class, 'handleWebhook']);
});

// ── Capa 4: Rutas de datos personales — requieren autenticación ───────────
// auth:sanctum garantiza que el usuario está logueado (401 si no)
// El ownership de cada recurso se verifica dentro del controlador (403 si no es tuyo)

// Checkout: límite estricto — es una operación financiera
Route::middleware(['auth:sanctum', 'throttle:5,1'])->group(function () {
    Route::post('/checkout', [CartController::class, 'checkout']);
});

// Consulta de órdenes: límite moderado — solo lectura
Route::middleware(['auth:sanctum', 'throttle:30,1'])->group(function () {
    Route::get('/orders', [CartController::class, 'orders']);
    Route::get('/orders/{order}', [CartController::class, 'showOrder']);
});

// ── Capa 5: WhatsApp orders ────────────────────────────────────────────────
// Sin auth por diseño (pedidos desde WhatsApp), sin descuento de inventario
Route::middleware('throttle:20,1')->group(function () {
    Route::post('/whatsapp-order', [CartController::class, 'whatsappOrder']);
});
