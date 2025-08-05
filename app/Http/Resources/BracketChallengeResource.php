<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BracketChallengeResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_public' => $this->is_public,
            // 'start_date' => $this->start_date,
            // 'end_date' => $this->end_date,
            'start_date' => $this->start_date->toDateString(), // Format date
            'end_date' => $this->end_date->toDateString(),     // Format date
            'league_id' => $this->league_id,
            'league' => $this->whenLoaded('league', $this->league->abbr),
            'bracket_data' => $this->bracket_data,
            'rounds' => $this->whenLoaded('rounds', function () {
                return RoundResource::collection($this->rounds);
            }),
        ];
    }
}
