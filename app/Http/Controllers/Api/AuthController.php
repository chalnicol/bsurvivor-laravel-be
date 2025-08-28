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

use App\Notifications\WelcomeUserNotification;
use App\Notifications\VerifyEmailNotification;

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
            'email_verification_token' => Str::random(60),
            'token_expires_at' => now()->addDay(),
        ]);

        //event(new Registered($user));
        if ( $user) {
            session()->put('pending_email_verification', $user->email);
            // Mail::to($user->email)->queue(new VerifyEmailMailable($user));
            $user->notify(new VerifyEmailNotification());
        }

        return response()->json([
            'message' => 'User registered successfully!',
            // 'user' => $user,
            // 'token' => $token,
        ], 201);
    }

    public function sendVerificationEmail()
    {
        $email = session()->get('pending_email_verification');

        if ( !$email ) {
            return response()->json(['message' => 'No pending email verification found.'], 404);
        }

        $user = User::where('email', $email)->first();

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

        // Check if the user is already verified
        if ($user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => 'Email is already verified.'
            ]);
        }

        // Check if the token is valid (matches the user and is not expired)
        // Note: You need a 'token_expires_at' column in your users table for this to work
        if ($user->email_verification_token !== $request->token || is_null($user->token_expires_at) || now()->gt($user->token_expires_at)) {
            throw ValidationException::withMessages([
                'token' => 'Invalid or expired verification token.'
            ]);
        }

        // Mark the user as verified
        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->token_expires_at = null;
        $user->save();

        // Log the user in to their session
        Auth::login($user);

        // Get the authenticated user and load roles/permissions
        $authenticatedUser = Auth::user();
        $authenticatedUser->load('roles.permissions');

        $url = '/profile';
        $authenticatedUser->notify(new WelcomeUserNotification($url, $user->id));

        return response()->json([
            'message' => 'Email verified successfully! You have been logged in.',
            'user' => new UserResource($authenticatedUser)
        ]);
    }

    public function login(Request $request)
    {
        // 1. Validate credentials
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $throttleKey = strtolower($request->input('email')) . '|' . $request->ip();
        $decayMinutes = 1;
        $maxAttempts = 5;

        // 2. Check for too many login attempts
        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . $seconds . ' seconds.'
            ], 429);
        }

        // 3. Attempt to authenticate the user
        if (!Auth::attempt($request->only('email', 'password'))) {
            RateLimiter::hit($throttleKey, $decayMinutes * 60);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // 4. Get the authenticated user
        $user = $request->user();

        if (!$user->hasVerifiedEmail()) {
            Auth::guard('web')->logout();
            session()->put('pending_email_verification', $user->email);
            throw ValidationException::withMessages([
                'email' => 'Please verify your email to log in.'
            ]);
        }

        // 5. Check if the authenticated user is blocked
        if ($user->isBlocked()) {
            Auth::guard('web')->logout();
            // $request->session()->invalidate();
            // Log out the blocked user
            return response()->json([
                'message' => 'Your account has been blocked. Please contact support.'
            ], 403);
        }

        // 6. On successful login and no block, clear the throttle counter
        RateLimiter::clear($throttleKey);

        $user->load('roles.permissions');

        return response()->json([
            'message' => 'Logged in successfully!',
            'user' => new UserResource($user)
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
