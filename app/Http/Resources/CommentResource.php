<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'body' => $this->body,
            'created_at' => $this->created_at?->toISOString(), // Human-readable date
            'updated_at' => $this->updated_at?->toISOString(),
            'parent_id' => $this->parent_id,
            'user_id' => $this->user_id,
            'user' => new UserMiniResource($this->whenLoaded('user')), // Load user if it's eager loaded
            // Conditionally load parent comment if it's eager loaded
            'parent' => new CommentResource($this->whenLoaded('parent')),

            // Conditionally load replies if they're eager loaded and not an empty collection
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            'replies_count' => $this->when(isset($this->replies_count), $this->replies_count),

            'votes' => [
                'likes' => $this->when(isset($this->likes_only_count), $this->likes_only_count),
                'dislikes' => $this->when(isset($this->dislikes_only_count), $this->dislikes_only_count),
            ],

            'user_vote' => $this->whenLoaded('myVote', function () {
                // Check if a vote exists
                if ($this->myVote) {
                    // Return 'like' or 'dislike' based on the is_like boolean
                    return $this->myVote->is_like ? 'like' : 'dislike';
                }
                // Return null if no vote was found for the authenticated user
                return null;
            }),
        ];
    }
}
