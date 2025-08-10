<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource; 
    
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter; // Add this

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:15|min:5|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed', // 'confirmed' checks for password_confirmation field
        ]);

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Generate token for the newly registered user
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully!',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // 1. Check if user exists and is blocked FIRST
        if ($user && $user->isBlocked()) {
            return response()->json([
                'message' => 'Your account has been blocked. Please contact support.'
            ], 403); // 403 Forbidden
        }

        $throttleKey = strtolower($request->input('email')) . '|' . $request->ip();
        $decayMinutes = 1; // Time in minutes to reset the throttle
        $maxAttempts = 5; // Number of maximum login attempts

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . $seconds . ' seconds.'
            ], 429); // 429 Too Many Requests
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            RateLimiter::hit($throttleKey, $decayMinutes * 60); // Decay in seconds

            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // On successful login, clear the throttle counter
        RateLimiter::clear($throttleKey);

        $user = $request->user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully!',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete(); // Revoke the current token

        return response()->json(['message' => 'Logged out successfully!']);
    }

    public function user(Request $request)
    {
        $user = $request->user()->load('roles.permissions');

        // return response()->json($request->user());
        return new UserResource($user);
    }
}
