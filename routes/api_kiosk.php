<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;

Route::group([
    'middleware' => 'api',
    'prefix' => 'kiosk'
], function () {
    // Rutas abiertas
    Route::post('get_session',[App\Http\Controllers\AllowedMacDevicesController::class, 'get_session']);
    Route::post('save_photo',[App\Http\Controllers\KioskAuthenticationDataController::class, 'save_photo']);
    //Route::post('get_qualification_report',[App\Http\Controllers\ReportController::class, 'download_qualification_report']);
    // Rutas autenticadas con token
    Route::group([
        'middleware' => ['api_poa']
    ], function () {
        Route::get('/get_affiliate_loans/{id_affiliate}',[App\Http\Controllers\Loan\LoanController::class, 'get_information_current_loans']);
        Route::get('loan/{loan}/print/kardex',[App\Http\Controllers\Loan\LoanController::class, 'print_kardex']);
        Route::get('/all_contributions/{affiliate_id}', [App\Http\Controllers\Contribution\AppContributionController::class, 'all_contributions']);
        Route::get('/contributions_passive/{affiliate_id}', [App\Http\Controllers\Contribution\AppContributionController::class, 'printCertificationContributionPassive']);
        Route::get('/contributions_active/{affiliate_id}', [App\Http\Controllers\Contribution\AppContributionController::class, 'printCertificationContributionActive']);
    });
});
