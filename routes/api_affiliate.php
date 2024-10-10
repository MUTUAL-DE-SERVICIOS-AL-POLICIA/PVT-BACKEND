<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;

Route::group([
    'middleware' => 'api',
    'prefix' => 'affiliate'
], function () {
    //Rutas abiertas
    Route::patch('change_password', [App\Http\Controllers\Affiliate\AffiliateUserController::class, 'change_password']);
    Route::post('auth', [App\Http\Controllers\Affiliate\AffiliateUserController::class, 'auth']);
    // Rutas autenticadas con token
    Route::group([
        'middleware' => ['auth:sanctum']
    ], function () {
        //
        Route::post('upload_copy_affiliates_availability', [App\Http\Controllers\Affiliate\ImportAffiliatesController::class, 'upload_copy_affiliates_availability']);
        Route::post('validate_availability', [App\Http\Controllers\Affiliate\ImportAffiliatesController::class, 'validate_affiliates_availability']);
        Route::post('download_error_data_archive', [App\Http\Controllers\Affiliate\ImportAffiliatesController::class, 'download_error_data_archive']);
        Route::post('download_data_revision', [App\Http\Controllers\Affiliate\ImportAffiliatesController::class, 'download_data_revision']);
        Route::post('download_data_revision_suggestion', [App\Http\Controllers\Affiliate\ImportAffiliatesController::class, 'download_data_revision_suggestion']);
        Route::post('list_months_import_affiliates_availability', [App\Http\Controllers\Affiliate\ImportAffiliatesController::class, 'list_months_import_affiliates_availability']);
        Route::post('rollback_import_affiliates_availability', [App\Http\Controllers\Affiliate\ImportAffiliatesController::class, 'rollback_import_affiliates_availability']);
        Route::post('import_affiliates_availability_progress_bar', [App\Http\Controllers\Affiliate\ImportAffiliatesController::class, 'import_affiliates_availability_progress_bar']);
        Route::post('report_import_affiliates_availability', [App\Http\Controllers\Affiliate\ImportAffiliatesController::class, 'report_import_affiliates_availability']);
        Route::post('validate_import_affiliate_observation', [App\Http\Controllers\Affiliate\ImportAffiliatesController::class, 'validate_import_affiliate_observation']);
        Route::post('import_affiliate_observation', [App\Http\Controllers\Affiliate\ImportAffiliatesController::class, 'import_affiliate_observation']);
        Route::post('download_error_observation_archive', [App\Http\Controllers\Affiliate\ImportAffiliatesController::class, 'download_error_observation_archive']);
        //
        Route::get('/credential_status/{id}', [App\Http\Controllers\Affiliate\AffiliateUserController::class, 'credential_status']);
        Route::get('/credential_status/{id}', [App\Http\Controllers\Affiliate\AffiliateController::class, 'credential_status']);
        Route::get('credential_document/{id}',[App\Http\Controllers\Affiliate\AffiliateUserController::class, 'credential_document']);
        Route::apiResource('/address', App\Http\Controllers\Affiliate\AddressController::class)->only(['store','update','destroy']);
        Route::apiResource('/degree', App\Http\Controllers\Affiliate\DegreeController::class)->only(['index','show']);
        Route::apiResource('/unit', App\Http\Controllers\Affiliate\UnitController::class)->only(['index','show']);
        Route::apiResource('/category',App\Http\Controllers\Affiliate\CategoryController::class)->only(['index','show']);
        Route::apiResource('/pension_entity',App\Http\Controllers\Affiliate\PensionEntityController::class)->only(['index','show']);
        Route::apiResource('/affiliate_state',App\Http\Controllers\Affiliate\AffiliateStateController::class)->only(['index','show']);
        Route::post('store', [App\Http\Controllers\Affiliate\AffiliateUserController::class, 'store']);
        Route::get('/affiliate_record/{affiliate}', [App\Http\Controllers\Affiliate\AffiliateController::class, 'get_record']);
        Route::apiResource('/affiliate_ext', App\Http\Controllers\Affiliate\AffiliateController::class)->only(['index','show']);
        Route::apiResource('/spouse_ext', App\Http\Controllers\Affiliate\SpouseController::class)->only(['index','show']);
        Route::group([
            'middleware' => 'permission:show-affiliate'
        ], function () {
            Route::apiResource('/affiliate', App\Http\Controllers\Affiliate\AffiliateController::class)->only(['index','show']);
            Route::apiResource('/spouse', App\Http\Controllers\Affiliate\SpouseController::class)->only(['index','show']);
            Route::get('affiliate/{affiliate}/spouse', [App\Http\Controllers\Affiliate\AffiliateController::class, 'get_spouse']);
            Route::get('/affiliate/{affiliate}/address', [App\Http\Controllers\Affiliate\AffiliateController::class, 'get_addresses']);
        });
        Route::group([
            'middleware' => 'permission:update-affiliate-secondary'
        ], function () {
            Route::apiResource('spouse', App\Http\Controllers\Affiliate\SpouseController::class)->only('store');
            Route::patch('/affiliate/{affiliate}/address', [App\Http\Controllers\Affiliate\AffiliateController::class, 'update_addresses']);
        });

        Route::group([
            'middleware' => 'permission:update-affiliate-primary|update-affiliate-secondary'
        ], function () {
            Route::apiResource('affiliate', App\Http\Controllers\Affiliate\AffiliateController::class)->only('update');
            Route::apiResource('spouse', App\Http\Controllers\Affiliate\SpouseController::class)->only('update');
        });

        Route::group([
            'middleware' => 'permission:create-affiliate'
        ], function () {
            Route::apiResource('/affiliate', App\Http\Controllers\Affiliate\AffiliateController::class)->only(['store']);
        });
    });
});
