<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoundCustomResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'conference' => $this->conference,
            'name' => $this->name,
            'order_index' => $this->order_index,
            'matchups' => MatchupCustomResource::collection(
                $this->whenLoaded('matchups')
            ),
        ];
    }
}
