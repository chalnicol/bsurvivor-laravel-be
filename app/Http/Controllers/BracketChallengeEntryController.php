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
       $query = BracketChallengeEntry::with('bracket_challenge', 'user');

        // Get the search term from the request
        $searchTerm = $request->input('search');

        if ($request->filled('search')) {
            $trimmedSearchTerm = trim($searchTerm);

            // Start a new group of OR conditions for the search term
            $query->where(function ($q) use ($trimmedSearchTerm) {

                // 1. Search by BracketEntry ID (cast to string for LIKE, or direct match if exact)
                // We'll use a raw WHERE for casting to string to make it work with LIKE efficiently.
                // For exact ID matches, we can also add a direct equality check.
                $q->orWhere('id', $trimmedSearchTerm); // Direct match for numbers
                $q->orWhereRaw('CAST(id AS CHAR) LIKE ?', ['%' . $trimmedSearchTerm . '%']);


                // 2. Search by User ID (owner of the bracket entry)
                $q->orWhere('user_id', $trimmedSearchTerm); // Direct match for numbers
                $q->orWhereRaw('CAST(user_id AS CHAR) LIKE ?', ['%' . $trimmedSearchTerm . '%']);


                // 3. Search by User Name (case-insensitive "contains")
                $q->orWhereHas('user', function ($userQuery) use ($trimmedSearchTerm) {
                    // Adjust for your database (LOWER for MySQL/SQL Server, ILIKE for PostgreSQL)
                    $userQuery->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($trimmedSearchTerm) . '%']);
                });
            });
        }

        // Add any other default ordering or pagination
        $bracketChallengeEntries = $query->orderBy('created_at', 'desc')->paginate(10); // Or get() for all

        // Return a collection of resources, which automatically handles pagination wrapping
        return BracketChallengeEntryResource::collection($bracketChallengeEntries);
    }

    public function show(BracketChallengeEntry $bracketChallengeEntry)
    {
        //
        $bracketChallengeEntry->load('bracket_challenge.rounds.matchups.teams', 'bracket_challenge.league', 'user');

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
