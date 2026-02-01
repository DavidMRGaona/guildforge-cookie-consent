<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\CookieConsent\Http\Controllers\ConsentApiController;

/*
|--------------------------------------------------------------------------
| Cookie Consent Web Routes
|--------------------------------------------------------------------------
|
| Routes for the Cookie Consent module using web middleware for
| session-based functionality with Inertia.js frontend.
|
*/

Route::prefix('consentimiento-cookies')->group(function () {
    Route::get('/categorias', [ConsentApiController::class, 'categories'])->name('cookie-consent.categories');
    Route::get('/configuracion', [ConsentApiController::class, 'config'])->name('cookie-consent.config');
    Route::post('/consentir', [ConsentApiController::class, 'store'])->name('cookie-consent.store');
});
