<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Include roles and permissions
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name'); // Just send role names
            }),
            'permissions' => $this->all_permissions, // Use the accessor we defined in the User model
            // If you added 'can_access' accessor
            // 'can_access' => $this->can_access,
        ];
    }
}
