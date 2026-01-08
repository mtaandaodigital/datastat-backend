<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\RegistrantController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API routes (no authentication required)
Route::prefix('v1')->group(function () {
    
    // Course routes
    Route::prefix('courses')->group(function () {
        Route::get('/', [CourseController::class, 'index']);
        Route::get('/stats', [CourseController::class, 'stats']);
        Route::get('/{id}', [CourseController::class, 'show']);
    });
    
    // Registrant routes
    Route::prefix('registrants')->group(function () {
        Route::get('/', [RegistrantController::class, 'index']);
        Route::post('/', [RegistrantController::class, 'store']);
        Route::get('/stats', [RegistrantController::class, 'stats']);
        Route::get('/{id}', [RegistrantController::class, 'show']);
    });
    
});

// Protected API routes (authentication required)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    
    // Admin-only routes would go here
    // Route::middleware('admin')->group(function () {
    //     // Admin routes
    // });
    
});