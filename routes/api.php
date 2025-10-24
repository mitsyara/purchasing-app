<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Protected routes can be defined here
Route::middleware(['auth:sanctum'])->group(function () {
    // Get the authenticated user's details
    Route::get('/user', fn(Request $request) => $request->user());

    // Purchase Order API routes
    // Route::apiResource('purchase-orders', App\Http\Controllers\Api\PurchaseOrderController::class);
    Route::get('purchase-orders', [App\Http\Controllers\Api\PurchaseOrderController::class, 'index']);
    // Route::get('purchase-orders/{id}', [App\Http\Controllers\Api\PurchaseOrderController::class, 'show']);

    // Purchase Shipment API routes
});

    Route::get('purchase-orders/{id}', [App\Http\Controllers\Api\PurchaseOrderController::class, 'show']);
