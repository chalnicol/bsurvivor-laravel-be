<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'matchup_index' => $this->matchup_index,
            'wins_team_1' => $this->wins_team_1,
            'wins_team_2' => $this->wins_team_2,
            'winner_team_id' => $this->winner_team_id,
            'teams' => TeamResource::collection($this->whenLoaded('teams')), // Load teams here
            'winner' => $this->whenLoaded('winner', fn() => new TeamResource($this->winner)), // Load winner team here
            // Note: 'round_id' is usually not needed in the resource if 'matchup' is nested under 'round'
        ];

    }
}
