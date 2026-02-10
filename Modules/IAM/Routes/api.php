<?php

use Illuminate\Support\Facades\Route;
use Modules\IAM\Http\Controllers\PermissionController;
use Modules\IAM\Http\Controllers\RoleController;

/*
|--------------------------------------------------------------------------
| API Routes - IAM Module
|--------------------------------------------------------------------------
*/

Route::prefix('v1/iam')->middleware(['auth:sanctum'])->group(function () {

    // Role routes
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/{role}', [RoleController::class, 'show']);
        Route::put('/{role}', [RoleController::class, 'update']);
        Route::delete('/{role}', [RoleController::class, 'destroy']);

        // Role-specific actions
        Route::post('/{role}/permissions', [RoleController::class, 'assignPermissions']);
        Route::get('/{role}/permissions', [RoleController::class, 'permissions']);
        Route::get('/{role}/users', [RoleController::class, 'users']);
    });

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
