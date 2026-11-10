<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use app\Http\Controllers\Order;

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'data']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::post('/', [ProductController::class, 'store']);
    Route::put('/{id}', [ProductController::class, 'update']);
});

Route::prefix('menus')->group(function () {
    Route::get('/', [MenuController::class, 'data']);
    Route::get('/{id}', [MenuController::class, 'show']);
    Route::post('/', [MenuController::class, 'store']);
    Route::put('/{id}', [MenuController::class, 'update']);
});
Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'data']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::post('/', [OrderController::class, 'store']);
    Route::put('/{id}', [OrderController::class, 'update']);
    Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
    Route::post('/{id}/checkout', [OrderController::class, 'checkout']);
});