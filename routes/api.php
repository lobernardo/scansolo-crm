<?php

use App\Http\Controllers\Api\DealNoteController;
use App\Http\Controllers\Api\DealStageController;
use App\Http\Controllers\Api\InboundLeadController;
use App\Http\Controllers\WhatsappWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/whatsapp', WhatsappWebhookController::class)->name('webhook.whatsapp');

Route::middleware('api.bearer')->group(function () {
    Route::post('/leads/inbound', InboundLeadController::class)->name('api.leads.inbound');
    Route::patch('/deals/{deal}/stage', DealStageController::class)->name('api.deals.stage');
    Route::post('/deals/{deal}/notes', DealNoteController::class)->name('api.deals.notes');
});
