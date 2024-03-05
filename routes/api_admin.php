<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'admin'
], function () {
    // Rutas abiertas
    Route::get('demo', [App\Http\Controllers\Admin\UserController::class, 'demo']);//ruta de prueba
    // Rutas autenticadas con token
    Route::group([
        'middleware' => ['auth:sanctum']
    ], function () {
        Route::get('update_users_ci', [App\Http\Controllers\Admin\UserController::class, 'update_users_ci']);//EJECUCION SOLO UNA VEZ
        Route::apiResource('/role', App\Http\Controllers\Admin\RoleController::class)->only(['index', 'show']);
        Route::apiResource('/module', App\Http\Controllers\Admin\ModuleController::class)->only(['index', 'show']);
        Route::get('module/{module}/role', [App\Http\Controllers\Admin\ModuleController::class, 'get_roles']);
        Route::get('role/{role}/role_permissions', [App\Http\Controllers\Admin\RoleController::class, 'role_permissions']);
        Route::apiResource('/permission', App\Http\Controllers\Admin\PermissionController::class)->only(['index']);
        Route::patch('role/{role}/permission', [App\Http\Controllers\Admin\RoleController::class,'set_or_remove_permission']);
        Route::apiResource('/user', App\Http\Controllers\Admin\UserController::class)->only(['index', 'store','show','update']);
        Route::get('user/{user}/module_role_state_user', [App\Http\Controllers\Admin\UserController::class, 'module_role_state_user']);
        Route::patch('user/{user}/role', [App\Http\Controllers\Admin\UserController::class, 'set_or_remove_role']);
        //rutas de sincronizacion de usuarios
        Route::get('get_employees', [App\Http\Controllers\Admin\UserController::class, 'get_employees']);
        Route::get('sync_employees', [App\Http\Controllers\Admin\UserController::class, 'sync_employees']);
        //Reporte de calificacion de servicios
        Route::post('get_qualification_report',[App\Http\Controllers\ReportController::class, 'download_qualification_report']);
    });
});

