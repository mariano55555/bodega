<?php

use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them
| will be assigned to the "api" middleware group.
|
*/

// API v1 Routes (Authenticated)
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Current authenticated user
    Route::get('user', function (Request $request) {
        return $request->user()->load('company');
    });

    // Products API
    Route::apiResource('products', ProductController::class)->only(['index', 'show']);

    // Dashboard metrics
    Route::get('dashboard/metrics', function (Request $request) {
        $service = new \App\Services\DashboardService($request->user());

        return response()->json([
            'data' => $service->getMetrics(),
        ]);
    });
});

// Public API routes (no authentication required)
Route::prefix('v1/public')->group(function () {
    // API health check
    Route::get('health', function () {
        return response()->json([
            'status' => 'ok',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });
});
