<?php

use App\Http\Controllers\CreateInvoiceController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/create-invoice', CreateInvoiceController::class);
});

// Webhook routes (public, no authentication required)
Route::post('/webhook/sms', [WebhookController::class, 'handleSms']);
