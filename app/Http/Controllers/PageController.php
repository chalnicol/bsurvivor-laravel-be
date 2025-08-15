<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\League;
use App\Models\User;
use App\Models\BracketChallenge;
use App\Models\Team;
use App\Models\BracketChallengeEntry;

use App\Http\Resources\BracketChallengeResource;
use App\Http\Resources\BracketChallengeEntryResource;
use App\Http\Resources\RoundCustomResource;
use Carbon\Carbon;

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
        //get is_public and within date range
        $bracketChallenge = BracketChallenge::where('slug', $slug)
            ->where('is_public', true)
            // ->where('start_date', '<=', Carbon::now()->toDateString())
            // ->where('end_date', '>=', Carbon::now()->toDateString())
            ->first();

        if ( !$bracketChallenge ) {
            return response()->json([
                'message' => 'Bracket Challenge not found.',
            ]);
        }

        $bracketChallengeEntrySlug = "";

        // if (Auth::check()) {
        if (Auth::guard('sanctum')->check()) {

            $user = Auth::guard('sanctum')->user();

            $bracketChallengeEntry = BracketChallengeEntry::where('user_id', $user->id)
                ->where('bracket_challenge_id', $bracketChallenge->id)
                ->first();
            
            // $hasEntry = ($bracketChallengeEntry !== null);
            if ( $bracketChallengeEntry ) {
                $bracketChallengeEntrySlug = $bracketChallengeEntry->slug;
            }
        }

        $bracketChallenge->load('league', 'rounds.matchups.teams');
        
     
        return response()->json([
            'message' => 'Bracket Challenge fetched successfully.',
            'bracketChallenge' => new BracketChallengeResource($bracketChallenge),
            'bracketEntrySlug' => $bracketChallengeEntrySlug,
        ]); 
        
    }

    public function fetch_active_challenges()
    {
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
            ->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>=', Carbon::now())
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
    }

    public function fetch_ongoing_challenges () {

        $bracketChallenges = BracketChallenge::with(['entries' => function ($query) {
            $query->with('user')
                ->orderBy('correct_predictions_count', 'desc')
                ->limit(10);
        }])
            ->where('is_public', true)
            ->where('end_date', '<=', Carbon::now())
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'message' => "Bracket challenges fetched successfully",
            'challenges' => BracketChallengeResource::collection($bracketChallenges)
        ]);
    }

    public function get_top_entries (int $bracketChallengeId) 
    {
        $topEntries = BracketChallengeEntry::with('user')
        ->where('bracket_challenge_id', $bracketChallengeId) // Specify the active challenge
        ->orderBy('correct_predictions_count', 'desc')
        ->limit(10)
        ->get();

        return response()->json([
            'message' => 'Top entries fetched successfully.',
            'entries' => BracketChallengeEntryResource::collection($topEntries)
        ]);
    }

    

}
