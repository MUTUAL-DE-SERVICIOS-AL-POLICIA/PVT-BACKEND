<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;

Route::group([
    'middleware' => 'api',
    'prefix' => 'poa'
], function () {
    // Rutas abiertas
    Route::post('get_session',[App\Http\Controllers\AllowedMacDevicesController::class, 'get_session']);
    // Rutas autenticadas con token
    Route::group([
        'middleware' => ['api_poa']
    ], function () {
        Route::get('/get_affiliate_loans/{id_affiliate}',[App\Http\Controllers\Loan\LoanController::class, 'get_information_loan']);
        Route::get('loan/{loan}/print/kardex',[App\Http\Controllers\Loan\LoanController::class, 'print_kardex']);
    });
});
