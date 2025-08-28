<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\League;
use App\Models\User;
use App\Models\BracketChallenge;
use App\Models\Team;
use App\Models\Comment;
use App\Models\BracketChallengeEntry;

use App\Http\Resources\BracketChallengeResource;
use App\Http\Resources\BracketChallengeEntryResource;
use App\Http\Resources\RoundCustomResource;
use App\Http\Resources\CommentResource;

use Carbon\Carbon;
use App\Mail\LeaveMessageMailable; // Your custom mail class 
use Illuminate\Support\Facades\Mail; 

class PageController extends Controller
{
    //
    public function get_bracket_challenge_entry(string $slug) 
    {
        //..
        $bracketChallengeEntry = BracketChallengeEntry::where('slug', $slug)->first();

        if ( !$bracketChallengeEntry ) {
            return response()->json([
                'message' => 'Bracket Challenge Entry not found.',
            ]);
        }

        $bracketChallengeEntry->load([
            'bracketChallenge.rounds.matchups.teams', 
            'bracketChallenge.league', 
            'user', 
            'predictions'
        ]);

        return new BracketChallengeEntryResource($bracketChallengeEntry);

    }

    public function get_bracket_challenge(string $slug)
    {
      
        $now =Carbon::now('UTC')->toDateString();

        $bracketChallengeEntrySlug = "";

        //get is_public and within date range
        $bracketChallenge = BracketChallenge::where('slug', $slug)
            ->where('is_public', true)
            ->first();

        if (!$bracketChallenge ) {
            return response()->json([
                'message' => 'Bracket Challenge not found.',
            ], 404);
        }

        // Conditionally eager load the user's entry
        if (Auth::guard('sanctum')->check()) {
            $user = Auth::guard('sanctum')->user();
            $bracketChallenge->load(['entries' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }]);
        }

        // Load other relationships
        $bracketChallenge->load('league', 'rounds.matchups.teams', 'comments.user');

        // Check if the eager loaded collection is not empty
        $bracketChallengeEntry = $bracketChallenge->entries->first();

        // Pass the slug to the response if an entry was found
        $bracketChallengeEntrySlug = $bracketChallengeEntry ? $bracketChallengeEntry->slug : null;

        //pass if to show leaderboard when bracket challenge end date is after the current date
        $now = Carbon::now('UTC');

        // $endDate = new Carbon($bracketChallenge->end_date)->addDay();
        $endDate = $bracketChallenge->end_date->addDay();


        $showLeaderboard = $endDate->lessThan($now);

        return response()->json([
            'message' => 'Bracket Challenge fetched successfully.',
            'bracketChallenge' => new BracketChallengeResource($bracketChallenge),
            'bracketEntrySlug' => $bracketChallengeEntrySlug,
            'showLeaderboard' => $showLeaderboard
        ]);
        
    }

    public function get_challenges(string $type)
    {   

        $now = Carbon::now('UTC')->toDateString();
        // $now = Carbon::create(2025, 8, 16, 0, 0, 0, 'Asia/Manila');
        
        if ($type === 'active' ) {

            // Initialize userId to null.
            $userId = null;
            // Check if a user is logged in and get their ID.

            // if (Auth::check()) {
            if (Auth::guard('sanctum')->check()) {
                // $userId = Auth::id();
                $userId = Auth::guard('sanctum')->id();
            }

            $bracketChallenges = BracketChallenge::with('league')
                ->where('is_public', true)
                ->where('start_date', '<=',  $now)
                ->where('end_date', '>=',  $now)
                // // Conditionally eager load the entries if a user is authenticated.
                ->when($userId, function ($query, $userId) {
                    $query->with(['entries' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    }]);
                })
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'message' => 'Challenges fetched successfully!',
                'challenges' => BracketChallengeResource::collection($bracketChallenges)
            ]);

        }else {

            //..
            $bracketChallenges = BracketChallenge::with(['entries' => function ($query) {
                $query->with('user')
                    ->orderBy('correct_predictions_count', 'desc')
                    ->limit(10);
            }])
                ->where('is_public', true)
                ->where('end_date', '<', $now)
                ->orderBy('id', 'desc')
                ->get();

            return response()->json([
                'message' => "Bracket challenges fetched successfully",
                'challenges' => BracketChallengeResource::collection($bracketChallenges)
            ]);
        }
        

       
    }

    public function get_all_challenges(Request $request)
    {   

        $query = BracketChallenge::with('league')
            ->where('is_public', true);
            
        if ($request->filled('search')) {
            $searchTerm = '%' . strtolower(trim($request->input('search'))) . '%';

            $query->where(function ($q) use ($searchTerm) {
                // 1. Search by Bracket Entry Name
                $q->whereRaw('LOWER(name) LIKE ?', [$searchTerm]);

                $q->orWhereRaw('LOWER(MONTHNAME(start_date)) LIKE ?', [$searchTerm])
                    ->orWhereRaw('YEAR(start_date) LIKE ?', [$searchTerm])
                    ->orWhereRaw('LOWER(MONTHNAME(end_date)) LIKE ?', [$searchTerm])
                    ->orWhereRaw('YEAR(end_date) LIKE ?', [$searchTerm]);
                //
                $q->orWhereHas('league', function ($leagueQuery) use ($searchTerm) {
                    $leagueQuery->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
                                ->orWhereRaw('LOWER(abbr) LIKE ?', [$searchTerm]);
                });
            });
        }

        $bracketChallengeEntries = $query->orderBy('created_at', 'desc')->paginate(10);

        return BracketChallengeResource::collection($bracketChallengeEntries);

    }

    public function get_leaderboard(Request $request, BracketChallenge $bracketChallenge)
    {
        $user = Auth::guard('sanctum')->user();
        $type = $request->input('type');

        if (!$bracketChallenge) {
            return response()->json([
                'message' => 'Bracket challenge not found.',
            ], 404);
        }
        // Base query to get all entries for the challenge
        $query = BracketChallengeEntry::with('user')
            ->where('bracket_challenge_id', $bracketChallenge->id);

        // Apply filter for "Friends" leaderboard
        if ($type == 'friends') {
            // Assuming you have a `friends` relationship on your User model
            //$friendIds = $user->friends()->pluck('id')->toArray();
            //$query->whereIn('user_id', $friendIds);

            $friends = $user->friendsOfMine->merge($user->friendOf);
            $friendIds = $friends->pluck('id')->toArray();
            $query->whereIn('user_id', $friendIds);
        }

        // Get the top 10 entries based on the applied filter
        $topEntries = $query->orderByRaw("CASE WHEN status = 'won' THEN 3 WHEN status = 'active' THEN 2 WHEN status = 'eliminated' THEN 1 ELSE 0 END DESC")
            ->orderBy('correct_predictions_count', 'desc')
            ->limit(10)
            ->get();

        // Fetch the current user's entry (relevant to the applied filter)
        $userEntry = BracketChallengeEntry::where('bracket_challenge_id', $bracketChallenge->id)
            ->where('user_id', $user->id)
            ->with('user')
            ->first();
        
        // Check if user's entry is not null and not in the top 10 of the filtered list
        if ($userEntry) {
            if (!$topEntries->contains('user_id', $user->id)) {

                // Calculate user's rank within the specific leaderboard (Global or Friends)
                $rankQuery = BracketChallengeEntry::where('bracket_challenge_id', $bracketChallenge->id);

                if ($type == 'friends') {
                    // Filter the rank query by friends only
                    //$friendIds = $user->friends()->pluck('id')->toArray();
                    $friends = $user->friendsOfMine->merge($user->friendOf);
                    $friendIds = $friends->pluck('id')->toArray();
                    $rankQuery->whereIn('user_id', $friendIds);
                }

                // The rank calculation must also respect the custom sort order
                $userRank = $rankQuery->orderByRaw("CASE WHEN status = 'won' THEN 3 WHEN status = 'active' THEN 2 WHEN status = 'eliminated' THEN 1 ELSE 0 END DESC")
                    ->orderBy('correct_predictions_count', 'desc')
                    ->get()
                    ->search(function ($item) use ($user) {
                        return $item->user_id == $user->id;
                    }) + 1;
                
                $userEntry->rank = $userRank;
                $userEntry->is_current_user_entry = true;

                // Add the user's entry to the collection
                $topEntries->push($userEntry);
            }
        }

        return response()->json([
            'message' => 'Top entries fetched successfully.',
            'id' => $bracketChallenge->id,
            'entries' => BracketChallengeEntryResource::collection($topEntries)
        ]);
    }

    public function leave_message(Request $request) 
    {
        $request->validate([
            'message' => 'required|string|max:255',
            'email' => 'required|email',
            'name' => 'required|string|max:255'
        ]);
        
        Mail::to('chalnicol@gmail.com')
            ->cc($request->input('email'))
            ->queue(new LeaveMessageMailable($request->name, $request->email, $request->message));
        
    }

    public function add_comments_to_challenge(Request $request, BracketChallenge $bracketChallenge)
    {
        $request->validate([
            'comment' => 'required|string|max:255'
        ]);

        $user = Auth::guard('sanctum')->user();

        $comment = $bracketChallenge->comments()->create([
            'body' => $request->input('comment'),
            'user_id' => $user->id,
        ]);

        $comment->load('user');

        return response()->json([
            'message' => 'Comment added successfully.',
            'comment' => new CommentResource($comment)
        ]);

    }

    public function update_comment(Request $request, Comment $comment)
    {
        $request->validate([
            'comment' => 'required|string|max:255'
        ]);

        $user = Auth::guard('sanctum')->user();

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to update this comment.'
            ], 403);
        }

        $comment->update([
            'body' => $request->input('comment')
        ]);

        $comment->load('user');

        return response()->json([
            'message' => 'Comment updated successfully.',
            'comment' => new CommentResource($comment)
        ]);
        
    }

    public function delete_comment(Comment $comment)
    {
        $user = Auth::guard('sanctum')->user();

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to delete this comment.'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted successfully.'
        ]);
    }




}
