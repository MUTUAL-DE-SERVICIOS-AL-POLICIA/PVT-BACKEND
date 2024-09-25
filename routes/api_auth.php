<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function () {
    // Rutas abiertas
    Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
    Route::post('/login_ext', [App\Http\Controllers\Auth\AuthController::class, 'login_ext']);
    // Rutas autenticadas con token
    Route::group([
        'middleware' => ['auth:sanctum']
    ], function () {
        Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout']);
        Route::get('/auth_user', [App\Http\Controllers\Auth\AuthController::class,'index']);
        Route::patch('/refresh', [App\Http\Controllers\Auth\AuthController::class, 'refresh']);

    });
});

