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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

use Illuminate\Auth\Events\Registered;

use Illuminate\Support\Str;

use App\Mail\VerifyEmailMailable; // Your custom mail class 

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
            'email_verification_token' => Str::random(60)
        ]);

        //event(new Registered($user));
        if ( $user) {
            session()->put('pending_email_verification', $user->email);

            Mail::to($user->email)->queue(new CustomVerifyEmail($user));
        }

        return response()->json([
            'message' => 'User registered successfully!',
            // 'user' => $user,
            // 'token' => $token,
        ], 201);
    }

    public function sendVerificationEmail(Request $request)
    {
        $email = session()->get('pending_email_verification');

        if ( !$email ) {
            return response()->json(['message' => 'No pending email verification found.'], 404);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user && $user->email_verified_at) {
            return response()->json(['message' => 'Email is already verified.'], 409);
        }

        Mail::to($user->email)->queue(new VerifyEmailMailable($user));
        
        return response()->json(['message' => 'Verification link sent!']);
    }

    public function verifyEmail (Request $request) 
    {

        // Validate the email and token sent from your React app
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
        ]);

        // Find the user by their email
        $user = User::where('email', $request->email)->first();

        if ($user && $user->email_verified_at) {
            return response()->json(['message' => 'Email is already verified.'], 409);
        }

        // Check if the token matches
        if (!$user || $user->email_verification_token !== $request->token) {
            return response()->json(['message' => 'Invalid verification token.'], 401);
        }

        // Mark the user as verified
        $user->email_verified_at = now();
        $user->email_verification_token = null; // Clear the token for security
        $user->save();

        $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully!',
            'user' => $user,
        ])->cookie('sanctum_token', $token, 60 * 24 * 7, null, null, true, true);
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

        // $user = $request->user()->load('roles.permissions');

        $user = Auth::user();

        $user->load('roles.permissions');
        
        return response()->json([
            'message' => 'Logged in successfully!',
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request)
    {
         // Log the user out of the web guard
        Auth::guard('web')->logout();

        // Invalidate the session on the server
        $request->session()->invalidate();

        // Regenerate the CSRF token
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully!']);
    }

    public function user(Request $request)
    {
        $user = $request->user()->load('roles.permissions');

        return new UserResource($user);
    }


    


}
