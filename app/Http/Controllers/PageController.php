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

    private function findMatchupByOrderIndexAndMatchupIndex($challenge, $roundOrderIndex, $matchupIndex)
    {
        return $challenge->rounds->firstWhere('order_index', $roundOrderIndex)
            ->matchups->firstWhere('matchup_index', $matchupIndex);
    }

    public function get_bracket_challenge(string $slug)
    {
        $bracketChallenge = BracketChallenge::where('slug', $slug)->first();

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
       $bracketChallenges = BracketChallenge::with('league')
                    ->where('is_public', true)
                    ->orderBy('id', 'desc')
                    ->get();

        return response()->json([
            'message' => 'Challenges fetched successfully!',
            'challenges' => BracketChallengeResource::collection($bracketChallenges)
        ]);
    }


}
