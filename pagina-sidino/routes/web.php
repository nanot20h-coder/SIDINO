<?php

use App\Http\Controllers\StripeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SolicitudController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/checkout', [StripeController::class, 'checkout'])
    ->name('checkout');

Route::get('/success', [StripeController::class, 'success'])
    ->name('success');

Route::get('/cancel', [StripeController::class, 'cancel'])
    ->name('cancel');
    Route::post(
    '/enviar-solicitud',
    [SolicitudController::class, 'store']
);
