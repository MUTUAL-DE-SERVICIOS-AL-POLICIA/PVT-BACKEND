<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;

Route::group([
    'middleware' => 'api',
    'prefix' => 'app'
], function () {
    // Rutas abiertas
    Route::get('procedure_qr/{module_id}/{uuid}', [App\Http\Controllers\ProcedureQRController::class, 'procedure_qr']);
    // Rutas autenticadas con token
    Route::group([
        'middleware' => ['api_auth']
    ], function () {
        Route::get('/get_information_loan/{id_affiliate}',[App\Http\Controllers\Loan\LoanController::class, 'get_information_loan']);
        Route::get('/loan/{loan}/print/plan',[App\Http\Controllers\Loan\LoanController::class, 'print_plan']);

        
        Route::get('/all_contributions/{affiliate_id}', [App\Http\Controllers\Contribution\AppContributionController::class, 'all_contributions']);
        Route::get('/contributions_passive/{affiliate_id}', [App\Http\Controllers\Contribution\AppContributionController::class, 'printCertificationContributionPassive']);
        Route::get('/contributions_active/{affiliate_id}', [App\Http\Controllers\Contribution\AppContributionController::class, 'printCertificationContributionActive']);
    });
});
