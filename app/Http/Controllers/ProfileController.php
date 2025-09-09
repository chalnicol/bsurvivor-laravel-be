<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; // Import Auth facade
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail; 
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use App\Http\Resources\UserResource; 

use App\Models\User;
use App\Models\BracketChallenge;
use App\Models\BracketChallengeEntry;
use App\Models\BracketChallengeEntryPrediction;

use App\Http\Resources\BracketChallengeEntryResource;
use App\Http\Resources\RoundResourceCustom;
use App\Http\Resources\NotificationResource;

use App\Notifications\VerifyEmailNotification;
use App\Notifications\FriendRequestSentNotification; // Import the notification class

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
            $user->notify(new FriendRequestSentNotification($currentUser, $user->id));

        }

        return response()->json([
            'message' => "Friends have been updated successfully.",
        ], 200);

    }

    public function get_friends(Request $request)
    {
        $user = Auth::user();
        $type = $request->query('type', 'active');
        
        // Use an array to build the relationships to load dynamically
        $relationsToLoad = ['friendsOfMine', 'friendOf', 'friendRequestsSent', 'friendRequestsReceived'];
        
        // Create an empty collection for friends
        $friends = collect();

        // Only load the specific relationship based on the type
        if ($type === 'active') {
            $user->load(['friendsOfMine', 'friendOf']);
            $friends = $user->friendsOfMine->merge($user->friendOf);
        } else if ($type === 'received') {
            $user->load('friendRequestsReceived');
            $friends = $user->friendRequestsReceived;
        } else if ($type === 'sent') {
            $user->load('friendRequestsSent');
            $friends = $user->friendRequestsSent;
        }

        // Now, get the counts, but only once and after loading the data
        $user->loadCount($relationsToLoad);

        $friendsCount = [
            'active' => $user->friends_of_mine_count + $user->friend_of_count,
            'sent' => $user->friend_requests_sent_count,
            'received' => $user->friend_requests_received_count,
        ];

        return response()->json([
            'message' => 'Friends fetched successfully.',
            'friends' => $friends,
            'count' => $friendsCount,
        ]);
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

        // Use withExists() for performance instead of eager loading
        $users = User::where(function ($query) use ($searchTerm) {
                $query->where('username', 'like', '%' . $searchTerm . '%')
                    ->orWhere('fullname', 'like', '%' . $searchTerm . '%');
            })
            ->where('id', '!=', $currentUser->id)
            // Check for an existing friendship in either direction
            ->withExists(['friendsOfMine as is_friends' => function ($query) use ($currentUser) {
                $query->where('friend_id', $currentUser->id);
            }])
            ->withExists(['friendOf as is_friend_of' => function ($query) use ($currentUser) {
                $query->where('user_id', $currentUser->id);
            }])
            ->withExists(['friendOf as is_friends_of_me' => function ($query) use ($currentUser) {
                $query->where('user_id', $currentUser->id);
            }])
            // This checks if the currentUser has SENT a request to the user being searched.
            // It's a received request from the perspective of the searched user.
            ->withExists(['friendRequestsReceived as request_sent_by_me' => function ($query) use ($currentUser) {
                $query->where('user_id', $currentUser->id);
            }])
            // This checks if the currentUser has RECEIVED a request from the user being searched.
            // It's a sent request from the perspective of the searched user.
            ->withExists(['friendRequestsSent as request_received_by_me' => function ($query) use ($currentUser) {
                $query->where('friend_id', $currentUser->id);
            }])
            ->limit(20) // Use a higher limit for a more comprehensive sort
            ->get();

        // Map the users and determine their friendship status
        $mappedUsers = $users->map(function ($user) {
            $status = 'not_friends';

            // Check if a friendship exists in either direction
            if ($user->is_friends || $user->is_friend_of) {
                $status = 'friends';
            }
            // Correct logic: Check if a request has been received by the current user
            else if ($user->request_received_by_me) {
                $status = 'request_received';
            } 
            // Correct logic: Check if a request has been sent by the current user
            else if ($user->request_sent_by_me) {
                $status = 'request_sent';
            }
            
            return [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => $user->fullname,
                'status' => $status,
            ];
        })->sortBy(function ($user) {
            // Assign a numerical value for sorting to get the desired order
            return match ($user['status']) {
                'friends' => 2,
                'request_received' => 3,
                'request_sent' => 4,
                'not_friends' => 1,
            };
        })->values(); // Re-index the collection after sorting

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




    