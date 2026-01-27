<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\CookieConsent\Http\Controllers\ConsentApiController;

Route::prefix('cookie-consent')->group(function () {
    Route::get('/categories', [ConsentApiController::class, 'categories'])->name('cookie-consent.categories');
    Route::get('/config', [ConsentApiController::class, 'config'])->name('cookie-consent.config');
    Route::post('/consent', [ConsentApiController::class, 'store'])->name('cookie-consent.store');
});
