<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\BracketChallenge;
use App\Models\League;
use App\Models\Team;
use App\Http\Resources\BracketChallengeResource;


use App\Traits\BracketChallengeTrait;

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
            'name' => 'required|string|max:255|unique:bracket_challenge,name',
            'description' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
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

        // Retrieve the selected league based on the ID
        $selectedLeague = $bracketChallenge->league;

        // Define initial rules for the other fields
        $rules = [
            // 'name' => 'required|string|max:255|unique:bracket_challenge,name,' . $bracketChallenge->id,
            'name' => 'required|string|max:255|unique:bracket_challenge,name,' . $bracketChallenge->id,
            'description' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
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

    
}
