<?php

use App\Http\Controllers\Api\DocumentationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

// API Documentation routes (no authentication required)
Route::prefix('v1/documentation')->group(function () {
    Route::get('/spec', [DocumentationController::class, 'specification']);
    Route::get('/modules/{module}', [DocumentationController::class, 'moduleSpecification']);
});
