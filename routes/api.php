<?php

use App\Http\Controllers\Api\AppBhpContentController;
use App\Http\Controllers\DokumentasiController;
use Illuminate\Support\Facades\Route;

Route::post('v1/integrations/instagram/dokumentasi', [DokumentasiController::class, 'storeFromInstagram'])
    ->middleware(['app-bhp', 'throttle:instagram-upload']);

Route::prefix('v1/app-bhp')->middleware(['app-bhp', 'throttle:120,1'])->group(function () {
    Route::get('rilis', [AppBhpContentController::class, 'rilis']);
    Route::get('rilis/{slug}', [AppBhpContentController::class, 'rilisDetail']);
    Route::get('kliping', [AppBhpContentController::class, 'kliping']);
    Route::get('kliping/{kliping}', [AppBhpContentController::class, 'klipingDetail']);
    Route::get('dokumentasi', [AppBhpContentController::class, 'dokumentasi']);
    Route::get('dokumentasi/{dokumentasi}', [AppBhpContentController::class, 'dokumentasiDetail']);
});
