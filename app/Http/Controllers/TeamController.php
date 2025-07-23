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
    public function index()
    {
        //
        $teams = Team::with('league')->get(); // Eager load the league relationship
        // $teams = Team::with('league') // Still eager load for the resource transformation
        //     ->join('leagues', 'teams.league_id', '=', 'leagues.id') // Join with the leagues table
        //     ->orderBy('leagues.name', 'asc') // Order by the name column in the leagues table
        //     ->orderBy('teams.conference', 'asc') // Keep your secondary order by conference
        //     ->select('teams.*') // IMPORTANT: Select all columns from the teams table to avoid conflicts
        //     ->get();

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
    public function show(Team $team)
    {
        //
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
