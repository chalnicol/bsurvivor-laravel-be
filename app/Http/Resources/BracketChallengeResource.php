<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

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
            'start_date' => $this->start_date, // Format date
            'end_date' => $this->end_date,     // Format date
            'league_id' => $this->league_id,
            'league' => $this->whenLoaded('league', $this->league->abbr),
            'bracket_data' => $this->bracket_data,
            'rounds' => $this->whenLoaded('rounds', function () {
                return RoundResource::collection($this->rounds);
            }),
            'comments' => $this->whenLoaded('comments', function () {
                return CommentResource::collection($this->comments);
            }),
        
            'created_at' => $this->created_at, // Format date,
            'updated_at' => $this->updated_at, // Format date,
            'entries' => $this->whenLoaded('entries', function () {
                return BracketChallengeEntryResource::collection($this->entries);
            }),
            'entries_count' => $this->when(isset($this->entries_count), $this->entries_count),
            
            // $this->mergeWhen(Auth::guard('sanctum')->check(), [
            //     'has_entry' => $this->entries->isNotEmpty(),
            // ]),
        ];
    }
}
