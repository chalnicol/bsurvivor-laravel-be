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
            'correct_predictions_count' => $this->correct_predictions_count,
            // 'last_round_survived' => $this->last_round_survived,
            'slug' => $this->slug,
            'bracket_challenge' => $this->whenLoaded('bracketChallenge', function () {
                return new BracketChallengeResource($this->bracketChallenge);
            }),
            'user' => $this->whenLoaded('user', function () {
                // return new UserResource($this->user);
                return [
                    'id' => $this->user->id,
                    'username' => $this->user->username,
                    'email' => $this->user->email,
                ];
            }),
            'predictions' => BracketChallengeEntryPredictionResource::collection($this->whenLoaded('predictions')),
            'created_at' => $this->created_at, // Format date,
            'updated_at' => $this->updated_at, // Format date,'

            'votes' => [
                'likes' => $this->when(isset($this->likes_only_count), $this->likes_only_count),
                'dislikes' => $this->when(isset($this->dislikes_only_count), $this->dislikes_only_count),
            ],
            'comments' => $this->whenLoaded('comments', function () {
                return CommentResource::collection($this->comments);
            }),
            'comments_count' => $this->when(isset($this->all_comments_count), $this->all_comments_count),

            'user_vote' => $this->whenLoaded('myVote', function () {
                // Check if a vote exists
                if ($this->myVote) {
                    // Return 'like' or 'dislike' based on the is_like boolean
                    return $this->myVote->is_like ? 'like' : 'dislike';
                }
                // Return null if no vote was found for the authenticated user
                return null;
            }),
            'rank' => $this->when(isset($this->rank), $this->rank),
            'is_current_user_entry' => $this->when(isset($this->is_current_user_entry), $this->is_current_user_entry),

            
        ];
    }
}
