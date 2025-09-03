<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Illuminate\Support\Facades\Mail; 

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

use App\Mail\LeaveMessageMailable; // Your custom mail class 

use Carbon\Carbon;

class PageController extends Controller
{
    //
    public function get_bracket_challenge_entry(string $slug) 
    {
        //..
        $bracketChallengeEntry = BracketChallengeEntry::where('slug', $slug)
            ->withCount('allComments')
            ->firstOrFail();

        $bracketChallengeEntry->load([
            'bracketChallenge.rounds.matchups.teams', 
            'bracketChallenge.league', 
            'user', 
            'predictions'
        ]);

        // return new BracketChallengeEntryResource($bracketChallengeEntry);
        return response()->json([
            'message' => "Bracket Challenge Entry fetched successfully",
            'entry' => new BracketChallengeEntryResource($bracketChallengeEntry),
            'totalCommentsCount' => $bracketChallengeEntry->all_comments_count
        ]);

    }

    public function get_bracket_challenge(string $slug)
    {
      
        //get is_public and within date range
        $bracketChallenge = BracketChallenge::where('slug', $slug)
            ->where('is_public', true)
            ->withCount('allComments')
            ->withCount('entries')
            ->firstOrFail();

        // Eager load all relationships in a single, chained call
        $bracketChallenge->load([
            'league',
            'rounds.matchups.teams',
        ]);

        // Conditionally eager load the user's entry using the `with` method
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            $bracketChallenge->load(['entries' => fn ($query) => $query->where('user_id', $user->id)]);
        }

        $bracketChallengeEntrySlug = optional($bracketChallenge->entries->first())->slug;

        // Simplify the date logic using Carbon's lessThanOrEqualTo method
        // Check if the current date is after the bracket challenge's end date
        $isPast = $bracketChallenge->end_date->addDay()->isPast();

        return response()->json([
            'message' => 'Bracket Challenge fetched successfully.',
            'bracketChallenge' => new BracketChallengeResource($bracketChallenge),
            'bracketEntrySlug' => $bracketChallengeEntrySlug,
            'isPast' => $isPast,
            'totalCommentsCount' => $bracketChallenge->all_comments_count,
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
                ->limit(3)
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
                ->limit(3)
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
            if (!$user) {
                return response()->json([
                    "message" => 'User not found.'
                ], 404);
            }

            $friends = $user->friendsOfMine->merge($user->friendOf);
            $friendIds = $friends->pluck('id')->toArray();
            $query->whereIn('user_id', $friendIds);
        }

        // Get the top 10 entries based on the applied filter
        $topEntries = $query->orderByRaw("CASE WHEN status = 'won' THEN 3 WHEN status = 'active' THEN 2 WHEN status = 'eliminated' THEN 1 ELSE 0 END DESC")
            ->orderBy('correct_predictions_count', 'desc')
            ->limit(10)
            ->get();

        if ($user) {
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

    public function get_comments(Request $request, string $resourceType, int $resourceId)
    {
        // Define the query for the comments relationship
        $commentsQuery = null;

        // Determine the resource type and find the corresponding model
        if ($resourceType === 'bracket-challenges') {
            $bracketChallenge = BracketChallenge::findOrFail($resourceId);
            $commentsQuery = $bracketChallenge->comments();
        } elseif ($resourceType === 'bracket-challenge-entries') {
            $bracketChallengeEntry = BracketChallengeEntry::findOrFail($resourceId);
            $commentsQuery = $bracketChallengeEntry->comments();
        } else {
            // Handle invalid resourceType gracefully
            return response()->json(['error' => 'Invalid resource type.'], 404);
        }
        
        // Ensure the query is an instance of a relationship
        if (!$commentsQuery instanceof MorphMany) {
            return response()->json(['error' => 'Resource does not have a comments relationship.'], 500);
        }

        // Existing comment retrieval logic
        $perPage = 10;
        $page = $request->query('page', 1); 

        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : 0;

        $query = $commentsQuery
            ->withCount(['likesOnly', 'dislikesOnly'])
            ->whereNull('parent_id')
            ->withUserAndReplyCount();

        if ($user) {
            $query->with('myVote');
        }

        $comments = $query
            ->orderByRaw('user_id = ? desc', [$userId])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        // Return the paginated comments using the resource collection
        return CommentResource::collection($comments);
    }

    public function add_comment(Request $request, string $resourceType, int $resourceId)
    {
        // Validate the request body
        $request->validate([
            'body' => 'required|string|max:255'
        ]);

        // Determine the model based on the resource type
        $model = null;
        $resource = null;

        if ($resourceType === 'bracket-challenges') {
            $model = BracketChallenge::class;
        } elseif ($resourceType === 'bracket-challenge-entries') {
            $model = BracketChallengeEntry::class;
        } else {
            return response()->json(['error' => 'Invalid resource type.'], 404);
        }
        
        // Find the resource instance or fail
        try {
            $resource = $model::findOrFail($resourceId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Resource not found.'], 404);
        }
        
        // Authenticate the user
        $user = Auth::guard('sanctum')->user();
        
        // Create the comment using the polymorphic relationship
        $comment = $resource->comments()->create([
            'body' => $request->input('body'),
            'user_id' => $user->id,
        ]);

        // Set the user relation for the resource and response
        $comment->setRelation('user', $user);

        return response()->json([
            'message' => 'Comment added successfully.',
            'comment' => new CommentResource($comment)
        ]);
    }

    // public function get_comments (Request $request, BracketChallenge $bracketChallenge) 
    // {
    //     $perPage = 10; // Number of comments per page
    //     $page = $request->query('page', 1); 

    //     $user = Auth::guard('sanctum')->user();
    //     $userId = $user ? $user->id : 0; // Use a default value if no user is authenticated

    //     $query = $bracketChallenge->comments()
    //         ->withCount(['likesOnly', 'dislikesOnly'])
    //         ->whereNull('parent_id') // We only paginate top-level comments
    //         ->withUserAndReplyCount();

    //     if ($user) {
    //         $query->with('myVote');
    //     }

    //     $comments = $query->orderByRaw('user_id = ? desc', [$userId])
    //                     ->orderBy('created_at', 'desc')
    //                     ->paginate($perPage, ['*'], 'page', $page);
        

    //     // Return the paginated comments using the resource collection
    //     return CommentResource::collection($comments);

    // }

    // public function add_comments_to_challenge(Request $request, BracketChallenge $bracketChallenge)
    // {
    //     $request->validate([
    //         'body' => 'required|string|max:255'
    //     ]);

    //     $user = Auth::guard('sanctum')->user();

    //     $comment = $bracketChallenge->comments()->create([
    //         'body' => $request->input('body'),
    //         'user_id' => $user->id,
    //     ]);

    //     $comment->setRelation('user',$user);

    //     return response()->json([
    //         'message' => 'Comment added successfully.',
    //         'comment' => new CommentResource($comment)
    //     ]);

    // }

    public function update_comment(Request $request, Comment $comment)
    {
        $request->validate([
            'updatedBody' => 'required|string|max:255'
        ]);

        $user = Auth::guard('sanctum')->user();

        if ($comment->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to update this comment.'
            ], 403);
        }

        $comment->update([
            'body' => $request->input('updatedBody')
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

    public function get_replies(Request $request, Comment $parentComment)
    {   
        $perPage = 5; // Number of comments per page
        $page = $request->query('page', 1); 

        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : 0; // Use a default value if no user is authenticated

        // $replies = $parentComment->replies()
        //     ->withUserAndReplyCount()
        //     ->orderByRaw('user_id = ? desc', [$userId])
        //     ->orderBy('created_at', 'desc')
        //     ->paginate($perPage, ['*'], 'page', $page);
     
        $query = $parentComment->replies()
            ->withCount(['likesOnly', 'dislikesOnly'])
            ->withUserAndReplyCount();

        if ($user) {
            $query->with('myVote');
        }

        $replies = $query->orderByRaw('user_id = ? desc', [$userId])
                        ->orderBy('created_at', 'desc')
                        ->paginate($perPage, ['*'], 'page', $page);

        return CommentResource::collection($replies);
    }

    public function add_reply_to_comment(Request $request, Comment $parentComment)
    {   
        // Check if the parent comment already has a parent (i.e., it's a reply itself)
        if ($parentComment->parent_id !== null) {
            return response()->json([
                'message' => 'You cannot reply to a reply. Please reply to the original comment instead.'
            ], 403); // 403 Forbidden status code is appropriate here
        }

        $request->validate([
            'body' => 'required|string|max:255'
        ]);

        $user = Auth::guard('sanctum')->user();

        // A reply needs the same commentable_id and type as its parent
        $reply = $parentComment->replies()->create([
            'body' => $request->input('body'),
            'user_id' => $user->id,
            'commentable_id' => $parentComment->commentable_id,
            'commentable_type' => $parentComment->commentable_type,
        ]);

        // Set the user relationship on the reply to avoid another database query
        $reply->setRelation('user', $user);

        return response()->json([
            'message' => 'Reply added successfully.',
            'reply' => new CommentResource($reply)
        ]);
    }


}
