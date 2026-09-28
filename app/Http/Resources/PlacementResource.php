<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlacementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'floor_id' => $this->floor_id,
            'x' => $this->x,
            'y' => $this->y,
            'location_description' => $this->location_description,
            'extinguisher' => $this->whenLoaded('extinguisher', fn (): ?ExtinguisherResource => $this->extinguisher ? new ExtinguisherResource($this->extinguisher) : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
