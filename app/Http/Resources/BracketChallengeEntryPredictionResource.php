<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BracketChallengeEntryPredictionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);
        return [
            'id' => $this->id,
            'matchup_id' => $this->matchup_id,
            'predicted_winner_team_id' => $this->predicted_winner_team_id,
            'teams' => $this->teams,
        ];
       
    }
}
