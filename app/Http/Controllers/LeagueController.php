<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\League;
use App\Http\Resources\LeagueResource;

class LeagueController extends Controller
{
    //
    public function index()
    {
        //
        // return response()->json(League::all());

        $leagues = League::all();
        // Return a collection of TeamResource
        return LeagueResource::collection($leagues);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function show(string $slug)
    {
        //
        $team = League::where('slug', $slug)->first();

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        return new LeagueResource($team);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $slug)
    {
        //
        $team = League::where('slug', $slug)->first();

        if (!$team) {
            return response()->json(['message' => 'League not found'], 404);
        }

        return new LeagueResource($team);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $slug)
    {
        //
        $league = League::where('slug', $slug)->first();

        try {
            $validated = $request->validate([
                'abbr' => 'required|string|max:255', // 'sometimes' validates only if field is present in request
                'name' => 'required|string|max:255|unique:leagues,name,' . $team->id, // Unique except for the current team's ID
            ]);
        } catch (ValidationException $e) {
            // Return validation errors with a 422 status
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422);
        }

        $league->update($validated);

        return new LeagueResource($team);

    }

}
