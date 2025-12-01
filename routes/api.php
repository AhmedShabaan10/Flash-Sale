<?php

use App\Http\Controllers\HoldController;
use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\OrderController;


Route::get('products/{id}', [ProductsController::class , 'show']);

Route::post('holds', [HoldController::class, 'store']);

Route::post('orders', [OrderController::class, 'store']);

Route::post('/payments/webhook', [PaymentWebhookController::class, 'handle']);
