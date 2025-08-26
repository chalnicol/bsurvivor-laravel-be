<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Illuminate\Validation\Rule;

use App\Http\Resources\UserResource; 

use App\Models\User;
use App\Models\BracketChallenge;
use App\Models\BracketChallengeEntry;
use App\Models\BracketChallengeEntryPrediction;

use App\Http\Resources\BracketChallengeEntryResource;
use App\Http\Resources\RoundResourceCustom;
use App\Mail\VerifyEmailMailable; // Your custom mail class 
use App\Mail\FriendRequestSentMailable; // Your custom mail class 
use Illuminate\Support\Facades\Mail; 

use App\Notifications\FriendRequestSent; // Import the notification class
use App\Notifications\FriendRequestReceived; // Import the notification class
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\NotificationResource;
use Illuminate\Notifications\DatabaseNotification;

use Carbon\Carbon;

class ProfileController extends Controller
{

    public function get_bracket_challenge_entries(Request $request)
    {

        // if (Auth::guard('sanctum')->check()) {
        //     // $userId = Auth::id();
        //     $user = Auth::guard('sanctum')->user;
        // }

        $user = Auth::guard('sanctum')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Authentication required.'
            ], 401);
        }

        $query = $user->entries()
            ->with('bracketChallenge.league');

        if ($request->filled('search')) {
            $searchTerm = '%' . strtolower(trim($request->input('search'))) . '%';

            $query->where(function ($q) use ($searchTerm) {
                // 1. Search by Bracket Entry Name
                $q->whereRaw('LOWER(name) LIKE ?', [$searchTerm]);

                $q->orWhereRaw('LOWER(status) LIKE ?', [$searchTerm]);

                $q->orWhereRaw('LOWER(MONTHNAME(created_at)) LIKE ?', [$searchTerm])
                    ->orWhereRaw('YEAR(created_at) LIKE ?', [$searchTerm]);

                $q->orWhereHas('bracketChallenge', function ($challengeQuery) use ($searchTerm) {
                    $challengeQuery->whereRaw('LOWER(name) LIKE ?', [$searchTerm]);
                });

                // 2. Search by League Name OR Abbreviation
                $q->orWhereHas('bracketChallenge.league', function ($leagueQuery) use ($searchTerm) {
                    $leagueQuery->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
                                ->orWhereRaw('LOWER(abbr) LIKE ?', [$searchTerm]);
                });
            });
        }

        $bracketChallengeEntries = $query->orderBy('created_at', 'desc')->paginate(10);

        return BracketChallengeEntryResource::collection($bracketChallengeEntries);
    }

    public function post_bracket_challenge_entry(Request $request) 
    {


        $userId =  Auth::guard('sanctum')->id();
       
        $bracketChallengeId = $request->input('bracket_challenge_id');

        // Get the current time for precise comparison
        $now = Carbon::now('UTC')->toDateString();

        //get bracket challenge
        $bracketChallenge = BracketChallenge::where('id', $bracketChallengeId)
            ->where('is_public', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->first();

        if ( !$bracketChallenge ) {
            return response()->json([
                'message' => 'Bracket challenge is not available or has expired.',
            ], 404);
        }

        $request->validate([
            'bracket_challenge_id' => [
                'required',
                'integer',
                'exists:bracket_challenges,id',
                 Rule::unique('bracket_challenge_entries')->where(function ($query) use ($userId, $bracketChallengeId) {
                    $query->where('user_id', $userId)
                          ->where('bracket_challenge_id', $bracketChallengeId);
                })
            ],
            // 'entry_data' => 'required|array',
            'predictions' => 'required|array',
            'predictions.*.matchup_id' => 'required|exists:matchups,id',
            'predictions.*.predicted_winner_team_id' => 'required|exists:teams,id',
            'predictions.*.teams' => 'required|array|size:2',
            'predictions.*.teams.*.id' => 'required|integer|exists:teams,id',
        ], [
            'bracket_challenge_id.unique' => 'You have already submitted an entry for this bracket challenge.',
            'predictions.matchup_id.exists' => 'Invalid matchup ID',
            'predictions.teams.exists' => 'Invalid team ID',
            'predictions.predicted_winner_team_id.exists' => 'Invalid predicted winner team ID',
            'predictions.teams.size' => 'Only two teams can be in a matchup',
            'predictions.teams.*.id.exists' => 'One or all team ids are invalid',
        ]);

        
        $padded_user_id = str_pad(Auth::id(), 3, '0', STR_PAD_LEFT);

        $padded_challenge_id = str_pad($request->bracket_challenge_id, 4, '0', STR_PAD_LEFT);


        $name = 'BCE-'. $padded_challenge_id . '-'  . Str::upper(Str::random(5)) . $padded_user_id;


        DB::beginTransaction();

        try {
            // 3. Create the main bracket entry record for the user.
            $entry = BracketChallengeEntry::create([
                'name' => $name,
                'user_id' => auth()->id(),
                'bracket_challenge_id' => $bracketChallengeId,
                'status' => 'active',
                // 'last_round_survived' => 0,
                'slug' => Str::slug($name),
            ]);

            // 4. Prepare the predictions for saving.
            $predictions = collect(request()->input('predictions'))->map(function ($prediction) use ($entry) {
                return new BracketChallengeEntryPrediction([
                    'bracket_challenge_entry_id' => $entry->id,
                    'matchup_id' => $prediction['matchup_id'],
                    'predicted_winner_team_id' => $prediction['predicted_winner_team_id'],
                    'teams' => $prediction['teams'],
                    'status' => 'active',
                ]);
            });

            // 5. Save all predictions at once using Eloquent's saveMany method.
            $entry->predictions()->saveMany($predictions);

            // 6. If everything succeeded, commit the transaction to make the changes permanent.
            DB::commit();

            return response()->json([
                'message' => 'Your bracket entry has been saved successfully!'
            ], 201); // 201 Created

        } catch (\Exception $e) {
            // 7. If any part of the process failed, roll back the transaction.
            DB::rollBack();

            return response()->json([
                'message' => 'An error occurred while saving your bracket. Please try again.'
            ], 500); // 500 Internal Server Error
        }
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

            Mail::to($request->email)->queue(new VerifyEmailMailable($user));

            Auth::guard('web')->logout();
            session()->put('pending_email_verification', $request->email);
            // $request->session()->invalidate();
            // $request->session()->regenerateToken();

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

    public function hasFriendshipWith(User $user): bool
    {
        return $this->belongsToMany(User::class, 'friendships')
                    // Check if the current user is in user_id AND target is in friend_id
                    ->where(function ($query) use ($user) {
                        $query->where('user_id', $this->id)
                            ->where('friend_id', $user->id);
                    })
                    // OR check if the target is in user_id AND current is in friend_id
                    ->orWhere(function ($query) use ($user) {
                        $query->where('user_id', $user->id)
                            ->where('friend_id', $this->id);
                    })
                    ->wherePivot('status', 'accepted')
                    ->exists();
    }

    public function friends_action (Request $request) 
    {

        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'action' => 'required|in:add,remove,accept,cancel,reject',
        ]);

        $currentUser = Auth::user();
        
        // $user = $request->input('user_id');

        $user = User::where('id', $request->input('user_id'))->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
        
        $action = $request->input('action');
        
        if (!$action) {
            return response()->json(['message' => 'Action not found.'], 404);
        }

        if ($action === 'remove') {
            //remove from friends of mine
            $currentUser->friendsOfMine()->detach($user->id);
            //remove from friend of
            $currentUser->friendOf()->detach($user->id);

        }else if ($action === 'cancel') {

            $request = $currentUser->friendRequestsSent()->where('friend_id', $user->id)->exists();
            if (!$request) {
                return response()->json(['message' => 'Friend request may have been accepted or rejected.'], 404);
            }
            //remove from friend requests sent
            $currentUser->friendRequestsSent()->detach($user->id);

        }else if ($action === 'accept') {

            // Find the pending request from the other user
            $request = $currentUser->friendRequestsReceived()->where('user_id', $user->id)->exists();

            if (!$request) {
                return response()->json(['message' => 'Friend request may have been cancelled.'], 404);
            }

            $user->friendRequestsSent()->updateExistingPivot($currentUser->id, ['status' => 'accepted']);
            
        }else if ($action === 'reject') {

            $request = $currentUser->friendRequestsReceived()->where('user_id', $user->id)->exists();
            if (!$request) {
                return response()->json(['message' => 'Friend request may have been cancelled.'], 404);
            }
            //remove from friend requests received
            $currentUser->friendRequestsReceived()->detach($user->id);

        }else if ($action === 'add') {

            // Prevent a user from friending themselves
            if ($currentUser->id === $user->id) {
                return response()->json(['message' => 'You cannot send a friend request to yourself.'], 400);
            }
            
            // Check if a friendship already exists or is pending
            if ($currentUser->hasAnyFriendshipWith($user)) {
                return response()->json(['message' => 'A friendship or pending request already exists.'], 409);
            }

            // Create the friendship record with 'pending' status
            $currentUser->friendRequestsSent()->attach($user->id, ['status' => 'pending']);

            //notify
            $user->notify(new FriendRequestSent($currentUser, $user));




        }

        //return friends..
        $currentUser->load(['friendsOfMine','friendOf', 'friendRequestsSent', 'friendRequestsReceived']);

        $friends = $currentUser->friendsOfMine->merge($currentUser->friendOf);

       
        return response()->json([
            'message' => "Friends have been updated successfully.",
            'friends' => [
                'active_friends' => $friends,
                'pending_friends' => $currentUser->friendRequestsSent,
                'to_accept_friends' => $currentUser->friendRequestsReceived,
                'blocked_friends' => []
            ]
        ], 200);

    }

    public function get_friends()
    {
        $user = Auth::user();

        // Eager load both relationships in a single query.
        $user->load(['friendsOfMine','friendOf', 'friendRequestsSent', 'friendRequestsReceived']);

        $friends = $user->friendsOfMine->merge($user->friendOf);

        return response()->json([
            'message' => 'Friends fetched successfully.',
            'friends' => [
                'active_friends' => $friends,
                'pending_friends' => $user->friendRequestsSent,
                'to_accept_friends' => $user->friendRequestsReceived,
                'blocked_friends' => []
            ]
        ], 200);
    }

    public function search_users(Request $request) 
    {

        $searchTerm = $request->input('search', "");

        if (empty($searchTerm)) {
            return response()->json([
                'message' => 'Users fetched successfully empty.',
                'users' => [],
            ]);
        }

        $currentUser = Auth::user();

        //$currentUser->load('friends', 'friendRequestsSent', 'friendRequestsReceived');

        // Get the users from the database, excluding the current user.
        $users = User::where(function ($query) use ($searchTerm) {
            $query->where('username', 'like', '%' . $searchTerm . '%')
                ->orWhere('email', 'like', '%' . $searchTerm . '%');
        })
        // ->whereNotIn('id', $excludeIds)
        ->where('id', '!=', $currentUser->id)
        ->orderBy('id', 'desc')
        ->limit(5)
        ->get();

        $mappedUsers = $users->map(function ($user) use ($currentUser) {
            return [
                'id' => $user->id,
                'username' => $user->username,
            ];
        });

        return response()->json([
            'message' => 'Users fetched successfully.',
            'users' => $mappedUsers,
        ]);

    }

    public function getUnreadCount()
    {

        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'count' => 0,
            ]);
        }

        $user = Auth::guard('sanctum')->user();

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'count' => $unreadCount,
        ]);
    }

    public function get_notifications()
    {
        $user = Auth::user();

        // Get all notifications for the user
        $notifications = $user->notifications()->latest()->paginate(10);

        return NotificationResource::collection($notifications);

    }

    public function mark_read_notification (Request $request)
    {
        // Validate the request to ensure a notification ID is present
        $request->validate([
            'notification_id' => 'required|string',
        ]);

        $notification = DatabaseNotification::find($request->input('notification_id'));
        
        // Check if the notification exists and belongs to the authenticated user for security
        if (!$notification || $notification->notifiable_id != Auth::id()) {
            return response()->json(['message' => 'Notification not found or unauthorized.'], 404);
        }
        
        $notification->markAsRead();
        
        return response()->json(['message' => 'Notification marked as read.'], 200);
    }

    public function delete_notification (DatabaseNotification $notification)
    {
        $notification->delete();
        return response()->json(['message' => 'Notification deleted successfully.'], 200);
    }

   

}




    