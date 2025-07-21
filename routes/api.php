<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ProfileController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Public routes (no authentication required)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

// Protected routes (require authentication with Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', [AuthController::class, 'user']); // Get authenticated user's details
    Route::put('/user/profile', [ProfileController::class, 'updateProfile']);
    Route::put('/user/password', [ProfileController::class, 'updatePassword']);
    Route::delete('/user', [ProfileController::class, 'deleteAccount']);
    
    // Route::middleware('role:admin')->group(function () {
    //     Route::get('/users', [UserController::class, 'index']);
    //     Route::post('/users', [UserController::class, 'store']);
    //     Route::put('/users/{user}', [UserController::class, 'update']);
    //     Route::delete('/users/{user}', [UserController::class, 'destroy']);
    // });

    
    // Add your basketball survivor application API routes here, e.g.:
    // Route::resource('leagues', LeagueController::class);
    // Route::post('leagues/{league}/join', [LeagueController::class, 'join']);
    // Route::get('games/{game}/picks', [PickController::class, 'getUserPicks']);
});