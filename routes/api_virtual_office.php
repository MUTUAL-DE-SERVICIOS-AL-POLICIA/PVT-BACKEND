<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'api',
    'prefix' => 'virtual_office'
], function () {
    // Rutas abiertas
    Route::get('procedure_list', [App\Http\Controllers\EconomicComplement\EcoComProcedureController::class, 'listProcedures']);
});
