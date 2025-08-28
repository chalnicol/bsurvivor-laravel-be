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
        ];
    }
}
