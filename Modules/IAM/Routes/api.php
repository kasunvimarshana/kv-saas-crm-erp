<?php

use Illuminate\Support\Facades\Route;
use Modules\IAM\Http\Controllers\PermissionController;

/*
|--------------------------------------------------------------------------
| API Routes - IAM Module
|--------------------------------------------------------------------------
*/

Route::prefix('v1/iam')->middleware(['auth:sanctum'])->group(function () {
    
    // Permission routes
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::get('/active', [PermissionController::class, 'active']);
        Route::get('/search', [PermissionController::class, 'search']);
        Route::get('/module/{module}', [PermissionController::class, 'byModule']);
        Route::post('/generate-crud', [PermissionController::class, 'generateCrud']);
        Route::get('/{id}', [PermissionController::class, 'show']);
        Route::put('/{id}', [PermissionController::class, 'update']);
        Route::delete('/{id}', [PermissionController::class, 'destroy']);
        
        // Assign permissions
        Route::post('/assign/role/{roleId}', [PermissionController::class, 'assignToRole']);
        Route::post('/assign/user/{userId}', [PermissionController::class, 'assignToUser']);
    });
});
