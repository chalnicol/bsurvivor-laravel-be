<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Exception\Auth\IdTokenVerificationFailed;
use Kreait\Laravel\Firebase\Facades\Firebase; // Import the Firebase Facade
use App\Http\Resources\UserResource;

class SocialLoginController extends Controller
{
    
    
  public function socialLogin(Request $request)
  {
      $request->validate([
          'idToken' => 'required|string',
          'provider' => 'required|in:google,facebook',
      ]);

      $idToken = $request->input('idToken');
      $provider = $request->input('provider');

      try {

          $firebaseAuth = Firebase::auth();

          $verifiedIdToken = $firebaseAuth->verifyIdToken($idToken);

          $uid = $verifiedIdToken->claims()->get('sub');
          $email = $verifiedIdToken->claims()->get('email');
          $name = $verifiedIdToken->claims()->get('name');

          // Generate a unique username based on the user's name
          $username = $this->generateUniqueUsername($name);


          // 3. FIND OR CREATE THE USER IN YOUR DATABASE
          // Use the email as the single source of truth
          $user = User::where('email', $email)->first();

          if ($user) {
              // Check if user is blocked
              if ($user->isBlocked()) {
                  return response()->json(['message' => 'Your account has been blocked.'], 403);
              }
              
              // If a traditional user logs in with social, link the account
              if (!$user->firebase_uid) {
                  $user->firebase_uid = $uid;
              }
              if (!$user->username) {
                  $user->username = $username;
              }
              $user->save();

          } else {
              // User does not exist, so create a new one
              $user = User::create([
                  'fullname' => $name,
                  'username' => $username,
                  'email' => $email,
                  'firebase_uid' => $uid,
                  'email_verified_at' => now(),
                  'password' => null, // Password is null for social users
              ]);
          }
          
          // 4. LOG THE USER INTO LARAVEL'S AUTHENTICATION SYSTEM
          Auth::login($user);

          // 5. Return a successful response
          $user->load('roles.permissions');

          return response()->json([
              'message' => 'Logged in successfully',
              'user' => new UserResource($user)
          ]);

      } catch (\Kreait\Firebase\Exception\Auth\IdTokenVerificationFailed $e) {
          // Token is invalid or expired
          return response()->json(['error' => 'Invalid or expired authentication token.'], 401);
      } 
      // catch (\Exception $e) {
      //     // General error
      //     return response()->json(['error' => 'An authentication error occurred.'], 500);
      // }
  }

  private function generateUniqueUsername(string $name): string
  {
      // 1. Generate a base username from the user's name (e.g., "John Doe" -> "johndoe")
      $username = strtolower(str_replace(' ', '', $name));

      // Fallback if the name is empty or results in an empty string
      if (empty($username)) {
          $username = 'user';
      }

      // 2. Check for uniqueness and append a random string if needed
      $originalUsername = $username;
      $i = 0;
      while (User::where('username', $username)->exists()) {
          $username = $originalUsername . random_int(1000, 9999);
          $i++;
          
          // Add a safety break to prevent infinite loops (e.g., after 5 attempts)
          if ($i > 5) {
              $username = $originalUsername . uniqid();
              break;
          }
      }
      
      // Ensure the username is no more than 15 characters long
      return substr($username, 0, 15);
  }
}