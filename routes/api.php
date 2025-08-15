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
use App\Http\Controllers\AdminPageController;
use App\Http\Controllers\BracketChallengeController;
use App\Http\Controllers\BracketChallengeEntryController;
use App\Http\Controllers\PageController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Public routes (no authentication required)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

Route::get('/bracket-challenges/active', [PageController::class, 'fetch_active_challenges']);
Route::get('/bracket-challenges/ongoing', [PageController::class, 'fetch_ongoing_challenges']);
Route::get('/bracket-challenges/{slug}', [PageController::class, 'get_bracket_challenge']);

Route::get('/bracket-challenge-entries/{slug}', [PageController::class, 'get_bracket_challenge_entry']);

// Route::get('/top-entries/{bracketChallengeId}', [PageController::class, 'get_top_entries']);

// Protected routes (require authentication with Sanctum)
Route::middleware(['auth:sanctum', 'user.blocked'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', [AuthController::class, 'user']); // Get authenticated user's details

    Route::get('/user/bracket-challenge-entries', [ProfileController::class, 'get_bracket_challenge_entries']);
    Route::post('/user/bracket-challenge-entries', [ProfileController::class, 'post_bracket_challenge_entry']);
    Route::put('/user/profile', [ProfileController::class, 'updateProfile']);
    Route::put('/user/password', [ProfileController::class, 'updatePassword']);
    Route::delete('/user', [ProfileController::class, 'deleteAccount']);
    
    
    //.admin role..

    Route::group(['middleware' => ['role:admin']], function () {

        Route::get('/admin/leagues', [LeagueController::class, 'index']);
        Route::get('/admin/leagues/{league}', [LeagueController::class, 'show']);
        Route::post('/admin/leagues', [LeagueController::class, 'store']);
        Route::get('/admin/leagues/{league}/edit', [LeagueController::class, 'edit']);
        Route::put('/admin/leagues/{league}', [LeagueController::class, 'update']);
        Route::delete('/admin/leagues/{league}', [LeagueController::class, 'destroy']);

        Route::get('/admin/totals', [AdminPageController::class, 'index']);
        Route::get('/admin/teams_and_leagues', [AdminPageController::class, 'getTeamsAndLeagues']);

        Route::get('/admin/users', [UserController::class, 'index']);
        Route::get('/admin/users/{user}', [UserController::class, 'show']);
        Route::patch('/admin/users/{user}/toggleBlock', [UserController::class, 'toggleBlockUser']);
        Route::patch('/admin/users/{user}/updateRoles', [UserController::class, 'updateUserRoles']);

        Route::get('/admin/bracket-challenge-entries', [BracketChallengeEntryController::class, 'index']);
        Route::get('/admin/bracket-challenge-entries/{bracketChallengeEntry}', [BracketChallengeEntryController::class, 'show']);
        Route::delete('/admin/bracket-challenge-entries/{bracketChallengeEntry}', [BracketChallengeEntryController::class, 'destroy']);
      

        Route::get('/admin/bracket-challenges', [BracketChallengeController::class, 'index']);
        Route::get('/admin/bracket-challenges/{bracketChallenge}', [BracketChallengeController::class, 'show']);
        Route::get('/admin/bracket-challenges/{bracketChallenge}/edit', [BracketChallengeController::class, 'edit']);
        Route::post('/admin/bracket-challenges', [BracketChallengeController::class, 'store']);
        Route::put('/admin/bracket-challenges/{bracketChallenge}', [BracketChallengeController::class, 'update']);
        Route::delete('/admin/bracket-challenges/{bracketChallenge}', [BracketChallengeController::class, 'destroy']);

        Route::put('/admin/bracket-challenges/{bracketChallenge}/update', [BracketChallengeController::class, 'updateMatchups']);
        Route::put('/admin/bracket-challenges/{bracketChallenge}/reset', [BracketChallengeController::class, 'resetMatchups']);

        Route::get('/admin/teams', [TeamController::class, 'index']);
        Route::get('/admin/teams/{team}', [TeamController::class, 'show']);
        Route::get('/admin/teams/{team}/edit', [TeamController::class, 'edit']);
        Route::post('/admin/teams', [TeamController::class, 'store']);
        Route::put('/admin/teams/{team}', [TeamController::class, 'update']);
        Route::delete('/admin/teams/{team}', [TeamController::class, 'destroy']);

        Route::get('/admin/roles', [RoleController::class, 'getAllRoles']);
        Route::get('/admin/roles-with-permissions', [RoleController::class, 'getAllRolesWithPermissions']);



    });

    // Add your basketball survivor application API routes here, e.g.:
    // Route::resource('leagues', LeagueController::class);
    // Route::post('leagues/{league}/join', [LeagueController::class, 'join']);
    // Route::get('games/{game}/picks', [PickController::class, 'getUserPicks']);
});