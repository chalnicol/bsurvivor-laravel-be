<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\TeamResource;
use App\Models\Team;

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
        $perPage = 10; // You can make this configurable or pass it from the frontend

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
    }

    /**
     * Display the specified resource.
     */
    public function show($slug)
    {
        $team = Team::where('slug', $slug)->firstOrFail();

        $team->load('league'); // Load league for a single team

        return new TeamResource($team);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        //
    }
}
