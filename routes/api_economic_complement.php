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
    });
});
