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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'abbr' => $this->abbr,
            'logo' => $this->logo,
            'slug' =>  $this->slug,    
            'conference' => $this->conference,
            // 'league' => new LeagueResource($this->whenLoaded('league')),
            'league' => $this->whenLoaded('league', $this->league->abbr),
        ];
    }
}
