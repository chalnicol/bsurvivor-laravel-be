<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BracketChallengeEntryResource extends JsonResource
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
            'bracket_challenge_id' => $this->bracket_challenge_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'last_round_survived' => $this->last_round_survived,
            'slug' => $this->slug,
            'bracket_challenge' => $this->whenLoaded('bracketChallenge', function () {
                return new BracketChallengeResource($this->bracketChallenge);
            }),
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
            'predictions' => BracketChallengeEntryPredictionResource::collection($this->whenLoaded('predictions')),
            'created_at' => $this->created_at->toDateString(), // Format date,
            'updated_at' => $this->updated_at->toDateString(), // Format date,'
            
        ];
    }
}
