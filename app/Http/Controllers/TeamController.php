<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Models\League;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator; 

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get the search term from the request
        $searchTerm = $request->query('search');
        
        // Define how many items per page
        $perPage = 15; // You can make this configurable or pass it from the frontend

        $query = Team::query();

        // Apply search filter if a search term is provided
        if ($searchTerm) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('abbr', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('conference', 'LIKE', '%' . $searchTerm . '%');
                // You can add more columns to search here, e.g.:
                // ->orWhere('league', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        $query->orderBy('id', 'desc')
            ->orderBy('updated_at', 'desc'); 

        // Paginate the results
        $teams = $query->paginate($perPage);

        return TeamResource::collection($teams);

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
        //
        $rules = [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'abbr' => [
                'required',
                'string',
                'max:10',
                // Use Rule::unique to scope the uniqueness check
                Rule::unique('teams')->where(function ($query) use ($request) {
                    // This closure adds a WHERE clause to the unique check.
                    // It ensures 'abbr' is unique only for teams with the same 'league_id'.
                    return $query->where('league_id', $request->league_id);
                }),
            ],
            'conference' => 'nullable|string|max:255|required_if:league,NBA',
            'league' => 'required|exists:leagues,abbr', // 'leagues' is the table name
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // For file upload (max 2MB)
            'logo_url' => 'nullable|url|max:2048', // For URL input
        ];

        // --- Custom Error Messages Array ---
        $messages = [
            'fname.required' => 'The team first name is absolutely required. Please provide it.',
            // 'name.unique' => 'A team with this name already exists. Choose a different one.',
            'fname.max' => 'The team first name cannot exceed 255 characters.',

            'lname.required' => 'The team last name is absolutely required. Please provide it.',
            // 'name.unique' => 'A team with this name already exists. Choose a different one.',
            'lname.max' => 'The team last name cannot exceed 255 characters.',

            'abbr.required' => 'The team abbreviation is missing.',
            'abbr.max' => 'The abbreviation must be 10 characters or less.',
            // For Rule::unique, you can target it specifically
            'abbr.unique' => 'This abbreviation is already taken for the selected league.',

            'league_id.required' => 'Please select a league for the team.',
            'league_id.exists' => 'The selected league is not valid.',

            'logo.image' => 'The uploaded file must be an image (jpeg, png, jpg, gif, svg).',
            'logo.mimes' => 'The logo file type is not supported. Please use jpeg, png, jpg, gif, or svg.',
            'logo.max' => 'The logo file size cannot exceed 2MB.',

            'logo_url.url' => 'Please enter a valid URL for the logo.',
        ];

        // 2. Custom Validation Logic (Ensuring ONE of logo or logo_url is present)
        $validator = Validator::make($request->all(), $rules, $messages);


        // 3. Check for validation failure
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422); // HTTP 422 Unprocessable Entity
        }


        $logoPath = null; // Initialize logo path

        // 4. Handle Logo Storage (if a file was uploaded)
        if ($request->hasFile('logo')) {
            // Store the file in storage/app/public/logos
            // 'public' disk is configured in config/filesystems.php
            $logoPath = $request->file('logo')->store('logos', 'public');
            // Convert the internal storage path to a public URL for access
            $logoPath = Storage::url($logoPath);
        }
        // 5. Handle Logo URL (if a URL was provided)
        elseif ($request->filled('logo_url')) {
            $logoPath = $request->input('logo_url');
        }

        $league = League::where('abbr', $request->input('league'))->first();
        if ( !$league ) {
            return response()->json([
                'message' => 'League not found',
            ], 404); // HTTP 404 Not Found
        };

        // )
        // 6. Create the team)
        $team = Team::create([
            'fname' => $request->input('fname'),
            'lname' => $request->input('lname'),
            'abbr' => $request->input('abbr'),
            'conference' => $request->input('conference') ?? null,
            'league_id' => $league->id,
            'logo' => $logoPath, // This will be null if neither logo nor logo_url was provided
            'slug' => Str::slug($request->input('fname') . ' ' . $request->input('lname')), // Ensure slug is generated
        ]);

        // 7. Return a successful JSON response
        return response()->json([
            'message' => 'Team created successfully!',
            'team' => $team // Return the created team data
        ], 201); // HTTP 201 Created

    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {

        $team->load('league'); // Load league for a single team

        return new TeamResource($team);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        
        $team->load('league'); // Load league for a single team

        return new TeamResource($team);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        //
        $rules = [
            // 'fname' => 'required|string|max:255|unique:teams,name,' . $team->id,
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'abbr' => [
                'required',
                'string',
                'max:10',
                // Use Rule::unique to scope the uniqueness check
                Rule::unique('teams')->where(function ($query) use ($request) {
                    // This closure adds a WHERE clause to the unique check.
                    // It ensures 'abbr' is unique only for teams with the same 'league_id'.
                    return $query->where('league_id', $request->league_id);
                })->ignore($team->id, 'id'),
            ],
            'conference' => 'nullable|string|max:255|required_if:league,NBA',
            'league' => 'required|exists:leagues,abbr', // 'leagues' is the table name
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // For file upload (max 2MB)
            'logo_url' => 'nullable|url|max:2048', // For URL input
        ];

       

        // --- Custom Error Messages Array ---
        $messages = [
            'fname.required' => 'The team name is absolutely required. Please provide it.',
            // 'fname.unique' => 'A team with this name already exists. Choose a different one.',
            'fname.max' => 'The team name cannot exceed 255 characters.',

            'lname.required' => 'The team name is absolutely required. Please provide it.',
            // 'lname.unique' => 'A team with this name already exists. Choose a different one.',
            'lname.max' => 'The team name cannot exceed 255 characters.',

            'abbr.required' => 'The team abbreviation is missing.',
            'abbr.max' => 'The abbreviation must be 10 characters or less.',
            // For Rule::unique, you can target it specifically
            'abbr.unique' => 'This abbreviation is already taken for the selected league.',

            'league_id.required' => 'Please select a league for the team.',
            'league_id.exists' => 'The selected league is not valid.',

            'logo.image' => 'The uploaded file must be an image (jpeg, png, jpg, gif, svg).',
            'logo.mimes' => 'The logo file type is not supported. Please use jpeg, png, jpg, gif, or svg.',
            'logo.max' => 'The logo file size cannot exceed 2MB.',

            'logo_url.url' => 'Please enter a valid URL for the logo.',
        ];

        // 2. Custom Validation Logic (Ensuring ONE of logo or logo_url is present)
        $validator = Validator::make($request->all(), $rules, $messages);



        // 3. Check for validation failure
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422); // HTTP 422 Unprocessable Entity
        }


        $logoPath = $team->logo; // Initialize logo path

        // 4. Handle Logo Storage (if a file was uploaded)
        if ($request->hasFile('logo')) {
            // Store the file in storage/app/public/logos
            // 'public' disk is configured in config/filesystems.php
            $logoPath = $request->file('logo')->store('logos', 'public');
            // Convert the internal storage path to a public URL for access
            $logoPath = Storage::url($logoPath);
        }
        // 5. Handle Logo URL (if a URL was provided)
        elseif ($request->filled('logo_url')) {
            $logoPath = $request->input('logo_url');
        }

        $league = League::where('abbr', $request->input('league'))->first();
        if ( !$league ) {
            return response()->json([
                'message' => 'League not found',
            ], 404); // HTTP 404 Not Found
        };

        $team->update([
            'fname' => $request->input('fname'),
            'lname' => $request->input('lname'),

            'abbr' => $request->input('abbr'),
            'conference' => $request->input('conference') ?? null,
            'league_id' => $league->id,
            'logo' => $logoPath, // This will be null if neither logo nor logo_url was provided
            'slug' => Str::slug($request->input('name') . ' ' . $request->input('lname')), // Ensure slug is generated
        ]);

        // 7. Return a successful JSON response
        return response()->json([
            'message' => 'Team updated successfully!',
            'team' => $team // Return the created team data
        ], 201); // HTTP 201 Created

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        //
        $team->delete();

        return response()->json([
            'message' => 'Team deleted successfully!',
        ]);
    }
}
