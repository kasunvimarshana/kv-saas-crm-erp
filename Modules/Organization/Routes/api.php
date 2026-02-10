<?php

use Illuminate\Support\Facades\Route;
use Modules\Organization\Http\Controllers\Api\OrganizationController;
use Modules\Organization\Http\Controllers\Api\LocationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {
    // Organization routes
    Route::apiResource('organizations', OrganizationController::class);
    Route::get('organizations/{id}/children', [OrganizationController::class, 'children']);
    Route::get('organizations/{id}/hierarchy', [OrganizationController::class, 'hierarchy']);
    Route::get('organizations/{id}/descendants', [OrganizationController::class, 'descendants']);

    // Location routes
    Route::apiResource('locations', LocationController::class);
    Route::get('locations/{id}/children', [LocationController::class, 'children']);
    Route::get('organizations/{organizationId}/locations', [LocationController::class, 'byOrganization']);
});
