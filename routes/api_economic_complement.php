<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;

Route::group([
    'middleware' => 'api',
    'prefix' => 'economic_complement'
], function () {
    // Rutas autenticadas con token
    Route::group([
        'middleware' => ['api_auth']
    ], function () {
        Route::get('/eco_com_procedure_list',[App\Http\Controllers\EconomicComplement\EcoComProcedureController::class,'index']);
        Route::get('/movement_list/{affiliate_id}', [App\Http\Controllers\EconomicComplement\EcoComMovementController::class, 'index']);
        Route::post('/register_devolution', [App\Http\Controllers\EconomicComplement\EcoComMovementController::class, 'storeDevolution']);
        Route::post('/register_direct_payment', [App\Http\Controllers\EconomicComplement\EcoComMovementController::class, 'storeDirectPayment']);
        Route::delete('/delete_movement/{affiliate_id}', [App\Http\Controllers\EconomicComplement\EcoComMovementController::class, 'softDeleteMovement']);
    });
});
