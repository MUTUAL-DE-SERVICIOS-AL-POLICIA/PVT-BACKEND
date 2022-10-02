<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'api', 
    'prefix'     => 'notification'
], function () {
    // Ruta para obtener los semestres
    Route::get('get_semesters', [App\Http\Controllers\Notification\NotificationController::class, 'get_semesters']);
    // Ruta para obtener las observaciones
    Route::get('get_observations/{module_id}', [App\Http\Controllers\Notification\NotificationController::class, 'get_observations']);
    // Ruta para obtener las modalidades de pago
    Route::get('get_modalities_payment/{state_type_id}', [App\Http\Controllers\Notification\NotificationController::class, 'get_modalities_payment']);
    // Ruta para obtener los tipos de beneficiarios
    Route::get('get_beneficiary_type', [App\Http\Controllers\Notification\NotificationController::class, 'get_beneficiary_type']);
    // Ruta para obtener las jerarquias
    Route::get('get_hierarchical_level', [App\Http\Controllers\Notification\NotificationController::class, 'get_hierarchical_level']);
    // Ruta para obtener las acciones
    Route::get('get_actions', [App\Http\Controllers\Notification\NotificationController::class, 'get_actions']);
    // Ruta para obtener el listado masivo de afiliados
    Route::post('list_to_notify', [App\Http\Controllers\Notification\NotificationController::class, 'list_to_notify']);
    // Ruta para notificar
    Route::post('send_mass_notification', [App\Http\Controllers\Notification\NotificationController::class, 'send_mass_notification']);
    // Ruta provisional para envio de notificaciones
    Route::post('send_notifications', [App\Http\Controllers\Notification\NotificationController::class, 'send_notifications']);
});