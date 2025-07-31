<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        // return [
        //     'id' => $this->id,
        //     'name' => $this->name,
        //     'abbr' => $this->abbr,
        //     'logo' => $this->logo,
        //     'slug' =>  $this->slug,    
        //     'conference' => $this->conference,
        //     'league_id' => $this->league_id,
        //     'league' => $this->whenLoaded('league', $this->league->abbr),
        //     // 'league' => new LeagueResource($this->whenLoaded('league')),
        //     // 'seed' => $this->whenPivotLoaded('bracket_challenge_team', function () {
        //     //     return $this->pivot->seed;
        //     // }),
        // ];
        
        $pivotData = [];
        if (isset($this->pivot)) {
            $pivotData = [
                'slot' => $this->pivot->slot,
                'seed' => $this->pivot->seed,
                // Add other pivot data if needed, like $this->pivot->score, etc.
            ];
        }
        
        $data = [
             'id' => $this->id,
            'name' => $this->name,
            'abbr' => $this->abbr,
            'logo' => $this->logo,
            'slug' =>  $this->slug,    
            'conference' => $this->conference,
            'league_id' => $this->league_id,
            'league' => $this->whenLoaded('league', $this->league->abbr),
        ];

        return array_merge($data, $pivotData);

        
    }
}
