<?php

declare(strict_types=1);

use App\Http\Controllers\Api\PaymentStatusWebhookController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/telegram', [TelegramWebhookController::class, 'handleWebhook'])->name('webhook.telegram');
Route::match(['get', 'post'], '/webhook/conversion', [WebhookController::class, 'handleConversion'])->name('webhook.conversion');
Route::match(['get', 'post'], '/webhook/payment-status/{gateway}', [PaymentStatusWebhookController::class, 'handlePaymentStatusUpdate'])->name('webhook.payment-status');
