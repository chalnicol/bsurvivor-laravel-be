<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

use App\Models\BracketChallengeEntry;
use App\Models\BracketChallenge;

use App\Http\Resources\BracketChallengeEntryResource;

class BracketChallengeEntryController extends Controller
{
    //

    public function index(Request $request) // Inject Request
    {

       $query = BracketChallengeEntry::with('bracketChallenge.league', 'user');

        if ($request->filled('search')) {
            $searchTerm = '%' . strtolower(trim($request->input('search'))) . '%';

            $query->where(function ($q) use ($searchTerm) {
                // 1. Search by Bracket Entry Name
                $q->whereRaw('LOWER(name) LIKE ?', [$searchTerm]);

                $q->orWhereRaw('LOWER(status) LIKE ?', [$searchTerm]);

                $q->orWhereRaw('LOWER(MONTHNAME(created_at)) LIKE ?', [$searchTerm])
                    ->orWhereRaw('YEAR(created_at) LIKE ?', [$searchTerm]);
                    
                // 2. Search by Username
                $q->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                    $userQuery->whereRaw('LOWER(username) LIKE ?', [$searchTerm]);
                });
                
                $q->orWhereHas('bracketChallenge', function ($challengeQuery) use ($searchTerm) {
                    $challengeQuery->whereRaw('LOWER(name) LIKE ?', [$searchTerm]);
                });

                // 3. Search by League Name
                $q->orWhereHas('bracketChallenge.league', function ($leagueQuery) use ($searchTerm) {
                    $leagueQuery->whereRaw('LOWER(name) LIKE ?', [$searchTerm])
                            ->orWhereRaw('LOWER(abbr) LIKE ?', [$searchTerm]);
                });
            });
        }

        $bracketChallengeEntries = $query->orderBy('id', 'desc')->paginate(10);

        return BracketChallengeEntryResource::collection($bracketChallengeEntries);
    }

    public function show(BracketChallengeEntry $bracketChallengeEntry)
    {
        //
        $bracketChallengeEntry->load([
            'bracketChallenge.rounds.matchups.teams', 
            'bracketChallenge.league', 
            'user',
            'predictions'
        ]);
        return new BracketChallengeEntryResource($bracketChallengeEntry);
    }

    public function edit(BracketChallengeEntry $bracketChallengeEntry)
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function destroy(BracketChallengeEntry $bracketChallengeEntry)
    {
        //
        $bracketChallengeEntry->delete();
        return response()->json([
            'message' => 'Entry deleted successfully!',
        ]);
    }

}
