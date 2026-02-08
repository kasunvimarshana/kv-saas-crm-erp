<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Http\Controllers\Api\CustomerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the Sales module.
|
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Customer routes
    Route::apiResource('customers', CustomerController::class);
    Route::get('customers/search', [CustomerController::class, 'search'])->name('customers.search');
});
