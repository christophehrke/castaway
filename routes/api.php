<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\ApiKeyController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\ExtensionController;
use App\Http\Controllers\Api\V1\IntentController;
use App\Http\Controllers\Api\V1\RecordingController;
use App\Http\Controllers\Api\V1\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public auth routes
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    // Public billing routes
    Route::get('billing/plans', [BillingController::class, 'plans']);

    // Paddle webhook (no auth, uses signature verification)
    Route::post('billing/webhook', [BillingController::class, 'webhook'])->middleware('verify.paddle');

    // Authenticated routes (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::apiResource('api-keys', ApiKeyController::class)->only(['index', 'store', 'destroy']);
        Route::apiResource('recordings', RecordingController::class)->only(['index', 'store', 'show', 'destroy']);

        // Intent endpoints
        Route::get('recordings/{recording}/intent', [IntentController::class, 'show']);
        Route::post('recordings/{recording}/intent/regenerate', [IntentController::class, 'regenerate']);

        // Workflow endpoints
        Route::post('recordings/{recording}/workflows/generate', [WorkflowController::class, 'generate']);
        Route::apiResource('workflows', WorkflowController::class)->only(['index', 'show']);

        // Billing endpoints (authenticated)
        Route::post('billing/checkout', [BillingController::class, 'checkout']);
        Route::get('billing/portal', [BillingController::class, 'portal']);
        Route::get('billing/usage', [BillingController::class, 'usage']);
    });

    // Admin routes (superadmin only)
    Route::middleware(['auth:sanctum', 'superadmin'])->prefix('admin')->group(function () {
        Route::get('metrics', [AdminController::class, 'metrics']);
        Route::get('recordings/failed', [AdminController::class, 'failedRecordings']);
        Route::post('recordings/{id}/reprocess', [AdminController::class, 'reprocess']);
        Route::get('command-runs', [AdminController::class, 'commandRuns']);
        Route::get('organizations', [AdminController::class, 'organizations']);
    });

    // API key authenticated routes (for extension)
    Route::middleware('auth.apikey')->group(function () {
        Route::get('extension/ping', [ExtensionController::class, 'ping']);
        Route::post('recordings/from-extension', [RecordingController::class, 'storeFromExtension']);
    });
});
