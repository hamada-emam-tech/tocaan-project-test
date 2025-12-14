<?php


use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentGatewayController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Fallback for unauthenticated API access
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');

// Public Product routes
Route::prefix('products')->group(function () {
    Route::get('/', [\App\Http\Controllers\ProductController::class, 'index']);
    Route::get('/{product}', [\App\Http\Controllers\ProductController::class, 'show']);
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'me']);
    });

    // Order routes
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);
        Route::put('/{id}', [OrderController::class, 'update']);
        Route::patch('/{id}/status', [OrderController::class, 'updateStatus'])->middleware('role:admin');
        Route::delete('/{id}', [OrderController::class, 'destroy']);

        // Order-centric payment actions
        Route::post('/{id}/pay', [PaymentController::class, 'store'])->middleware('payment_method');
        Route::get('/{orderId}/payments', [PaymentController::class, 'getByOrder']);
    });

    // Payment routes (Direct access)
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']); // Audit log view
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::get('/{id}/verify', [PaymentController::class, 'verify']);
    });

    // Available Payment Gateways
    Route::get('/payment-gateways', [PaymentGatewayController::class, 'index']);

    // System Settings routes
    Route::prefix('settings')->group(function () {
        Route::get('/', [\App\Http\Controllers\SettingController::class, 'index']);
        Route::middleware('role:admin')->put('/{key}', [\App\Http\Controllers\SettingController::class, 'update']);
    });

    // Product Product routes (Admin Write)
    Route::prefix('products')->middleware('role:admin')->group(function () {
        Route::post('/', [\App\Http\Controllers\ProductController::class, 'store']);
        Route::put('/{product}', [\App\Http\Controllers\ProductController::class, 'update']);
        Route::delete('/{product}', [\App\Http\Controllers\ProductController::class, 'destroy']);
    });
});
