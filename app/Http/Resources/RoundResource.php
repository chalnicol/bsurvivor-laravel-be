<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoundResource extends JsonResource
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
            'conference' => $this->conference,
            'name' => $this->name,
            'order_index' => $this->order_index,
            'matchups' => MatchupResource::collection($this->whenLoaded('matchups')), // Load matchups here
        ];

    }
}
