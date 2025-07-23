<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\RoleController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Public routes (no authentication required)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

// Protected routes (require authentication with Sanctum)
Route::middleware(['auth:sanctum', 'user.blocked'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', [AuthController::class, 'user']); // Get authenticated user's details
    Route::put('/user/profile', [ProfileController::class, 'updateProfile']);
    Route::put('/user/password', [ProfileController::class, 'updatePassword']);
    Route::delete('/user', [ProfileController::class, 'deleteAccount']);
    
    Route::group(['middleware' => ['role:admin']], function () {

        Route::get('/admin/leagues', [LeagueController::class, 'index']);
        Route::get('/admin/leagues/{slug}', [LeagueController::class, 'show']);

        Route::get('/admin/users', [UserController::class, 'index']);
        Route::get('/admin/users/{user}', [UserController::class, 'show']);
        Route::patch('/admin/users/{user}/toggleBlock', [UserController::class, 'toggleBlockUser']);
        Route::patch('/admin/users/{user}/updateRoles', [UserController::class, 'updateUserRoles']);

        Route::get('/admin/teams', [TeamController::class, 'index']);

        Route::get('/admin/roles', [RoleController::class, 'getAllRoles']);
        Route::get('/admin/roles-with-permissions', [RoleController::class, 'getAllRolesWithPermissions']);

    });

    
    // Add your basketball survivor application API routes here, e.g.:
    // Route::resource('leagues', LeagueController::class);
    // Route::post('leagues/{league}/join', [LeagueController::class, 'join']);
    // Route::get('games/{game}/picks', [PickController::class, 'getUserPicks']);
});