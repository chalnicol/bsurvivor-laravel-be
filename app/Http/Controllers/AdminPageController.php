<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\League;
use App\Models\User;
use App\Models\BracketChallenge;
use App\Models\Team;

use App\Http\Resources\LeagueResource;
use App\Http\Resources\TeamResource;

class AdminPageController extends Controller
{
    //
    public function index()
    {
        
        return response()->json([
            'message' => 'Resources totals fetched successfully.',
            'totals' => [
                'leagueTotal' => League::count(),
                'userTotal' => User::count(),
                'bracketChallengeTotal' => BracketChallenge::count(),
                'teamTotal' => Team::count(),
            ],
        ]); 
        
    }

    public function getTeamsAndLeagues () {

        return response()->json([
            'message' => 'Teams and Leagues fetched successfully.',
            'teams' => TeamResource::collection(Team::all()),
            'leagues' => LeagueResource::collection(League::all())
        ]); 

    }

    

}
