<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;

Route::group([
    'middleware' => 'api',
    'prefix' => 'report'
], function () {
    // Rutas autenticadas con token
    Route::group([
        'middleware' => ['auth:sanctum']
    ], function () {
        Route::get('report_affiliates_spouses', [App\Http\Controllers\ReportController::class, 'report_affiliates_spouses']);
        Route::get('report_retirement_funds', [App\Http\Controllers\ReportController::class, 'report_retirement_funds']);
        Route::get('report_payments_beneficiaries', [App\Http\Controllers\ReportController::class, 'report_payments_beneficiaries']);
    });
});
