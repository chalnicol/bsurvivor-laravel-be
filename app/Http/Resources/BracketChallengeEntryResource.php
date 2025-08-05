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
            'entry_data' => $this->entry_data,
            'status' => $this->status,
            'bracket_challenge' => $this->whenLoaded('bracket_challenge', function () {
                return new BracketChallengeResource($this->bracket_challenge);
            }),
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
        ];
    }
}
