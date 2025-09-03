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
use App\Notifications\EmailVerifiedNotification;
use App\Notifications\PasswordResetSuccess;


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

        if ( $user->email_verified_at ) {
            return response()->json(['message' => 'Email is already verified.'], 409);
        }
        
        // Mail::to($user->email)->queue(new VerifyEmailMailable($user));
        $user->notify(new VerifyEmailNotification());
        
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

        $isOldUser = now()->gt($user->created_at->addDays(2));

        if ( $isOldUser ) {
            $user->notify(new EmailVerifiedNotification($user->id, '/profile'));
        }else { 
            $user->notify(new WelcomeUserNotification($user->id, '/profile'));
        }
        
        session()->forget(['pending_email_verification']);
       
        return response()->json([
            'message' => 'Email verified successfully! Redirecting to login...',
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

        // Check if the authenticated user is blocked
        if ($user->isBlocked()) {
            Auth::guard('web')->logout();
            // $request->session()->invalidate();
            // Log out the blocked user
            return response()->json([
                'message' => 'Your account has been blocked. Please contact support.'
            ], 403);
        }

        if (!$user->hasVerifiedEmail()) {

            $user->email_verification_token = Str::random(60);
            $user->token_expires_at = now()->addDay();
            $user->save();

            $user->notify(new VerifyEmailNotification());

            Auth::guard('web')->logout();
            
            session()->put('pending_email_verification', $user->email);

            throw ValidationException::withMessages([
                'email' => 'Unverified email. A verification link has been sent to your email.'
            ]);
        }

        // On successful login and no block, clear the throttle counter
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

    public function update_profile(Request $request)
    {
        $user = $request->user(); // Get the authenticated user

        $request->validate([
            'username' => 'required|string|min:5|max:15|unique:users,username,' . $user->id,
            'email' => 'required|string|email|max:255|unique:users,email,'. $user->id,
        ]);

        $isEmailNew = $user->email !== $request->email;

        $user->username = $request->username;

        if ( $isEmailNew  ) {

            $user->email = $request->email;
            $user->email_verification_token = Str::random(60);
            $user->token_expires_at = now()->addDay();
            $user->email_verified_at = null;
            $user->save();

            $user->notify(new VerifyEmailNotification());

            Auth::guard('web')->logout();
            session()->put('pending_email_verification', $request->email);
          
            return response()->json([
                'message' => 'Profile updated. A new verification link has been sent to your new email address. You have been logged out for security.',
                'is_email_new' => true,
            ], 200);

        }

        $user->save();
        $user->load('roles.permissions');

        return response()->json([
            'message' => 'Profile updated successfully!',
            'user' => new UserResource($user), // Return the updated user data
            'is_email_new' => false,
        ]);
    }

    public function update_password(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) use ($user) {
                if (!Hash::check($value, $user->password)) {
                    $fail('The provided password does not match your current password.');
                }
            }],
            'password' => ['required', 'confirmed', 'min:8', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        $user->notify(new PasswordResetSuccess($user->id, '/profile'));

        return response()->json(['message' => 'Password updated successfully!']);
    }

    public function delete_account(Request $request)
    {
        $user = $request->user(); // Get the authenticated user

        // Delete the user record
        $user->delete();

        try {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } catch (\Exception $e) {
            // Log this, but don't fail the deletion if session cannot be invalidated for some reason.
            // This can happen if the session truly wasn't active or was already cleared.
            \Log::error("Failed to invalidate session during account deletion: " . $e->getMessage());
        }

        return response()->json(['message' => 'Account deleted successfully!']);
    }


}
