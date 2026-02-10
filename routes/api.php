<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AttributeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::resource('/products',ProductController::class);
Route::get('/products/{category}/{subcategory?}', [ProductController::class, 'index']);
Route::resource('/categories',CategoryController::class);
Route::resource('/attributes',AttributeController::class);


