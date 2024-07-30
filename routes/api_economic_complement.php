<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;

Route::group([
    'middleware' => 'api',
    'prefix' => 'economic_complement'
], function () {
    Route::patch('/register_payment_commitement/{movement_id}', [App\Http\Controllers\EconomicComplement\EcoComMovementController::class, 'register_payment_commitement']);
    // Rutas autenticadas con token
    Route::group([
        'middleware' => ['auth:sanctum']
    ], function () {
        Route::get('/eco_com_procedure_list',[App\Http\Controllers\EconomicComplement\EcoComProcedureController::class,'index']);
        Route::get('/economic_complement_list/{affiliate_id}',[App\Http\Controllers\EconomicComplement\EconomicComplementController::class,'ListEconomicComplement']);
        Route::get('/movement_list/{affiliate_id}', [App\Http\Controllers\EconomicComplement\EcoComMovementController::class, 'index']);
        Route::post('/register_devolution', [App\Http\Controllers\EconomicComplement\EcoComMovementController::class, 'storeDevolution']);
        Route::post('/register_direct_payment', [App\Http\Controllers\EconomicComplement\EcoComMovementController::class, 'storeDirectPayment']);
        Route::delete('/delete_movement/{affiliate_id}', [App\Http\Controllers\EconomicComplement\EcoComMovementController::class, 'softDeleteMovement']);
        Route::get('/report_eco_com_movement',[App\Http\Controllers\ReportController::class, 'report_overpayments']);
        Route::get('/show_details/{movement_id}', [App\Http\Controllers\EconomicComplement\EcoComMovementController::class, 'show_details']);

    });
});
