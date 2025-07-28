<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator; 

use App\Models\League;
use App\Http\Resources\LeagueResource;

class LeagueController extends Controller
{
    //
    public function index(Request $request)
    {
        
        // Get the search term from the request
        $searchTerm = $request->query('search');
        
        // Define how many items per page
        $perPage = 10; // You can make this configurable or pass it from the frontend

        $query = League::query();

        // Apply search filter if a search term is provided
        if ($searchTerm) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('abbr', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Paginate the results
        $query->orderBy('id', 'desc')
            ->orderBy('updated_at', 'desc'); 

        $teams = $query->paginate($perPage);

        return LeagueResource::collection($teams);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function show(League $league)
    {
        //
        $league->load('teams');

        return new LeagueResource($league);

    }

    public function store (Request $request)
    {
        //
        $rules = [
            'name' => 'required|string|max:255|unique:leagues,name',
            'abbr' => 'required|string|max:10|unique:leagues,abbr',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // For file upload (max 2MB)
            'logo_url' => 'nullable|url|max:2048', // For URL input
        ];

        // 2. Custom Validation Logic (Ensuring ONE of logo or logo_url is present)
        $validator = Validator::make($request->all(), $rules);

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

      
        // 6. Create the team)
        $league = League::create([
            'name' => $request->input('name'),
            'abbr' => $request->input('abbr'),
            'logo' => $logoPath, // This will be null if neither logo nor logo_url was provided
            'slug' => Str::slug($request->input('name')), // Ensure slug is generated
        ]);

        // 7. Return a successful JSON response
        return response()->json([
            'message' => 'League created successfully!',
            'league' => new LeagueResource($league) // Return the created team data
        ], 201); // HTTP 201 Created
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(League $league)
    {
        //
        // $league->load('teams');

        return new LeagueResource($league);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, League $league)
    {   
      
        //
        $rules = [
            'name' => 'required|string|max:255|unique:leagues,name,' . $league->id,
            'abbr' => 'required|string|max:10|unique:leagues,abbr,' . $league->id,
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // For file upload (max 2MB)
            'logo_url' => 'nullable|url|max:2048', // For URL input
        ]; 

        // 2. Custom Validation L ogic (Ensuring ONE of logo or logo_url is present)
        $validator = Validator::make($request->all(), $rules);

        // 3. Check for validation failure
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422); // HTTP 422 Unprocessable Entity
        }

        $logoPath = $league->logo; // Initialize logo path

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

        // 6. Create the team)
        $league->update([
            'name' => $request->input('name'),
            'abbr' => $request->input('abbr'),
            'logo' => $logoPath,
            'slug' => Str::slug($request->input('name')), // Ensure slug is generated
        ]);

        // 7. Return a successful JSON response
        return response()->json([
            'message' => 'League updated successfully!',
            'league' => $league // Return the created team data
        ], 201); // HTTP 201 Created
    }

    public function destroy(League $league)
    {
        //
        $league->delete();

        return response()->json([
            'message' => 'League deleted successfully!',
        ]);
    }


}
