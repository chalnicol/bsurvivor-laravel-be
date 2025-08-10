<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchupCustomResource extends JsonResource
{
   
    
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'matchup_index' => $this->matchup_index,
            'winner_team_id' => $this->winner_team_id,
            'teams' => TeamResource::collection($this->predictedTeams), // Use the dynamic teams
        ];
    }


}
