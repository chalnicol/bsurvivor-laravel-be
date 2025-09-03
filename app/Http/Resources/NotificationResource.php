<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
           'type' => class_basename($this->type), // Get only the class name (e.g., 'FriendRequestSent')
           'data' => $this->data, // This is already an array/object because Laravel casts it
           'read_at' => $this->read_at ? $this->read_at : null,
           'created_at' => $this->created_at,
           // You can add more derived data here if needed,
           // for example, to check if it's read or not:
           'is_read' => (bool) $this->read_at,
       ];

    }
}
