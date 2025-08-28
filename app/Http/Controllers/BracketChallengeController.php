<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\BracketChallenge;
use App\Models\League;
use App\Models\Team;
use App\Http\Resources\BracketChallengeResource;
use App\Http\Resources\RoundResource;
use Carbon\Carbon;

use App\Events\BracketChallengeUpdated;
use App\Traits\BracketChallengeTrait;
use Illuminate\Support\Facades\DB;

class BracketChallengeController extends Controller
{
    use BracketChallengeTrait;
    //
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) // Inject Request
    {
        $query = BracketChallenge::with('league');

        // Example: Only show public challenges by default
        //$query->where('is_public', true);

        // // Example: Allow filtering by league_id from query parameters
        // if ($request->has('league_id')) {
        //     $query->where('league_id', $request->input('league_id'));
        // }

        // Example: Allow searching by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        // Pagination: This will automatically wrap the results in a 'data' array
        // and include 'meta' and 'links' for pagination information.
        $bracketChallenges = $query->paginate(15); // Paginate with 15 items per page

        // Return a collection of resources, which automatically handles pagination wrapping
        return BracketChallengeResource::collection($bracketChallenges);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Step Validate the 'league' field first to get the league_id
        $request->validate([
            'league' => 'required|string|exists:leagues,abbr',
        ]);

        // Retrieve the selected league based on the ID
        // $selectedLeague = League::find($request->input('league'));
        $selectedLeague = League::where('abbr', $request->input('league'))->firstOrFail();

        // Define initial rules for the other fields
        $rules = [
            // 'name' => 'required|string|max:255|unique:bracket_challenge,name,' . $bracketChallenge->id,
            'name' => 'required|string|max:255|unique:bracket_challenges,name',
            'description' => 'nullable|string|max:255',
            'start_date' => 'required|date|after_or_equal:' . Carbon::now('UTC')->toDateString(),
            'end_date' => 'required|date|after:start_date',
            'is_public' => 'boolean',
            'is_public' => 'boolean',
        ];

        // Step 2: Conditionally define rules for 'teams'
        if ($selectedLeague && $selectedLeague->abbr === 'NBA') {
            $rules['bracket_data.teams'] = 'required|array';
            $rules['bracket_data.teams.east'] = 'required|array|size:8'; // Must have at least one East team
            $rules['bracket_data.teams.west'] = 'required|array|size:8'; // Must have at least one West team
            $rules['bracket_data.teams.east.*'] = [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where(function ($query) {
                    $query->where('conference', 'East');
                })
            ];
            $rules['bracket_data.teams.west.*'] = [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where(function ($query) {
                    $query->where('conference', 'West');
                })
            ];
        } else {
            // If not NBA, 'teams' is a simple array of IDs
            $rules['bracket_data.teams'] = 'required|array|size:8'; // will check..
            $rules['bracket_data.teams.*'] = 'required|integer|exists:teams,id';
        }

        
        $customMessages = [
            'start_date.after_or_equal' => 'Start date must be at least 2 days from now.',
            'end_date.after_or_equal' => 'End date must be at least 3 days from now.',
            'bracket_data.teams.east.required' => 'East conference teams are required.',
            'bracket_data.teams.east.size' => 'You must select exactly 8 East conference teams.',
            'bracket_data.teams.east.*.exists' => 'Please select a valid East conference team.',
            'bracket_data.teams.west.required' => 'West conference teams are required.',
            'bracket_data.teams.west.size' => 'You must select exactly 8 West conference teams',
            'bracket_data.teams.west.*.exists' => 'Please select a valid West conference team.',
            'bracket_data.teams.required' => 'Teams are required.',
            'bracket_data.teams.size' => 'You must select exactly 8 teams.',
            'bracket_data.teams.*.exists' => 'Please select a valid team.',
            // ... define messages for other rules and fields ...
        ];
            
        // Apply all the rules
        $validated = $request->validate($rules, $customMessages);

        // Create the new BracketChallenge record
        $bracketChallenge = BracketChallenge::create([
            'league_id' =>  $selectedLeague->id ,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? false,
            'bracket_data' => $validated['bracket_data'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'slug' => Str::slug($validated['name']), // Ensure slug is generated
        ]);

        if ( $bracketChallenge ) {
            $this->create_bracket($bracketChallenge, $validated['bracket_data']['teams']);
        }

        $bracketChallenge->load('league', 'rounds.matchups.teams');

        return response()->json([
            'message' => 'Challenge created successfully!',
            'challenge' => new BracketChallengeResource($bracketChallenge)
        ]);
        
    }

    /**
     * Display the specified resource.
     */
    public function show(BracketChallenge $bracketChallenge)
    {
        //
        $bracketChallenge->load('league', 'rounds.matchups.teams');
        
        return new BracketChallengeResource($bracketChallenge);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BracketChallenge $bracketChallenge)
    {
        //
        $bracketChallenge->load('league');

        return new BracketChallengeResource($bracketChallenge);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BracketChallenge $bracketChallenge)
    {

        $now = Carbon::now('UTC')->toDateString();

        // Retrieve the selected league based on the ID
        $selectedLeague = $bracketChallenge->league;

        // Define initial rules for the other fields
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bracket_challenges')->ignore($bracketChallenge->id),
            ],
            'description' => 'nullable|string|max:255',
            'is_public' => 'boolean',
        ];

        // Check if the challenge has not started yet
        if ($now->lessThan($bracketChallenge->start_date)) {
            // If it hasn't started, new start date must be in the future
            $rules['start_date'] = 'required|date|after_or_equal:today';
        } else {
            // If it has started, the start date cannot be changed.
            // It must be the same as the original start date to prevent it from moving.
            $rules['start_date'] = 'required|date|same:' . $bracketChallenge->start_date->toDateString();
        }

        
        $rules['end_date'] = [
            'required',
            'date',
            // The new end date must be after the start date.
            'after:start_date',
        ];

        // If the challenge has already started, we must ensure the new end date is in the future.
        if ($now->greaterThanOrEqual($bracketChallenge->start_date)) {
            $rules['end_date'][] = 'after_or_equal:today';
        }



        // Step 2: Conditionally define rules for 'teams'
    
        if ($selectedLeague && $selectedLeague->abbr === 'NBA') {
            $rules['bracket_data.teams'] = 'required|array';
            $rules['bracket_data.teams.east'] = 'required|array|size:8'; // Must have at least one East team
            $rules['bracket_data.teams.west'] = 'required|array|size:8'; // Must have at least one West team
            $rules['bracket_data.teams.east.*'] = [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where(function ($query) {
                    $query->where('conference', 'East');
                })
            ];
            $rules['bracket_data.teams.west.*'] = [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where(function ($query) {
                    $query->where('conference', 'West');
                })
            ];
        } else {
            // If not NBA, 'teams' is a simple array of IDs
            $rules['bracket_data.teams'] = 'required|array|size:8'; // will check..
            $rules['bracket_data.teams.*'] = 'required|integer|exists:teams,id';
        }

        
        $customMessages = [
            'start_date.after_or_equal' => 'Start date must be at least 2 days from now.',
            'end_date.after_or_equal' => 'End date must be at least 3 days from now.',
            'bracket_data.teams.east.required' => 'East conference teams are required.',
            'bracket_data.teams.east.size' => 'You must select exactly 8 East conference teams.',
            'bracket_data.teams.east.*.exists' => 'Please select a valid East conference team.',
            'bracket_data.teams.west.required' => 'West conference teams are required.',
            'bracket_data.teams.west.size' => 'You must select exactly 8 West conference teams',
            'bracket_data.teams.west.*.exists' => 'Please select a valid West conference team.',
            'bracket_data.teams.required' => 'Teams are required.',
            'bracket_data.teams.size' => 'You must select exactly 8 teams.',
            'bracket_data.teams.*.exists' => 'Please select a valid team.',
            // ... define messages for other rules and fields ...
        ];
            
        // Apply all the rules
        $validated = $request->validate($rules, $customMessages);

        // Create the new BracketChallenge record
        $bracketChallenge->update ([
            // 'league_id' =>  $selectedLeague->id ,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_public' => $validated['is_public'] ?? false,
            'bracket_data' => $validated['bracket_data'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'slug' => Str::slug($validated['name']), // Ensure slug is generated
        ]);

        if ($bracketChallenge) {
            $this->update_bracket($bracketChallenge, $validated['bracket_data']['teams']);
        }

        return response()->json([
            'message' => 'Challenge updated successfully!',
        ]);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BracketChallenge $bracketChallenge)
    {
        //
        $bracketChallenge->delete();
        return response()->json([
            'message' => 'Challenge deleted successfully!',
        ]);
    }

    public function updateMatchups(Request $request, BracketChallenge $bracketChallenge)
    {   

        // Check if the submission period for the challenge has ended.
        // The deadline is the end_date of the bracketChallenge.
        $now = Carbon::now('UTC');
        // $now = Carbon::create(2025, 8, 29, 0, 0, 0, 'UTC');

        // $deadline = new Carbon($bracketChallenge->end_date)->addDay();
        $deadline = $bracketChallenge->end_date->addDay();

        if ($deadline->greaterThanOrEqualTo($now)) {
            return response()->json([
                'message' => 'Matchups can only be updated after the submission period has ended.'
            ], 403); // Use 403 Forbidden to indicate the action is not allowed.
        }

        $request->validate([
            'matchups' => 'required|array',
            'matchups.*.matchup_id' => 'required|integer|exists:matchups,id',
            // 'matchups.*.winner_team_id' => 'required|exists:teams,id',
        ]);

        $matchups = $request->input('matchups', []);

        try {
            DB::beginTransaction();

            // Load the nested matchups relationship for the entire bracket challenge
            $bracketChallenge->load('rounds.matchups');

            // Create an efficient lookup table of all matchups in the challenge
            $allMatchups = $bracketChallenge->rounds
                ->flatMap(fn($round) => $round->matchups)
                ->keyBy('id');

            // Loop through the matchups submitted in the request
            foreach ($request->input('matchups', []) as $reqMatchup) {
                $matchupId = $reqMatchup['matchup_id'];
                $winnerId = $reqMatchup['winner_team_id'];
                $teamsData = $reqMatchup['teams'];

                // Find the corresponding matchup from our lookup table
                $dbMatchup = $allMatchups->get($matchupId);

                if ($dbMatchup) {
                    // Update the winner and save the change to the database
                    $dbMatchup->winner_team_id = $winnerId;

                    // Prepare data for the pivot table
                    if (!empty($teamsData)) {
                        $syncData = collect($teamsData)->mapWithKeys(function ($team) {
                            return [$team['id'] => ['slot' => $team['slot'], 'seed' => $team['seed']]];
                        })->toArray();
                        // Use sync() to update the teams and their slots in the pivot table
                        // This will detach any teams not in the new array and attach/update the new ones
                        $dbMatchup->teams()->sync($syncData);
                    }else {
                        $dbMatchup->teams()->sync([]);
                        $dbMatchup->winner_team_id = null;
                    }

                    $dbMatchup->save();
                }
            }

            DB::commit();

            $bracketChallenge->load('rounds.matchups.teams');

            event(new BracketChallengeUpdated($bracketChallenge));

            return response()->json([
                'message' => 'Bracket challenge updated successfully.',
                'rounds' => RoundResource::collection($bracketChallenge->rounds),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update matchups.',
                'error' => $e->getMessage()
            ], 500);
        }


    }


    

        




    
}
