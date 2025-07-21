<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth; // Import Auth facade

class ProfileController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user(); // Get the authenticated user

        $request->validate([
            'username' => ['nullable', 'string', 'min:5', 'max:15', 'unique:users,username,' . $user->id], // Example for username
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id], // Example for email
            // Add other validation rules for fields you allow to be updated
        ]);

        // Only update fields that are present in the request
        if ($request->has('username')) {
            $user->username = $request->username;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        // ... update other fields as needed

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully!',
            'user' => $user // Return the updated user data
        ]);
    }

    public function updatePassword(Request $request)
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

        return response()->json(['message' => 'Password updated successfully!']);
    }

    public function deleteAccount(Request $request)
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