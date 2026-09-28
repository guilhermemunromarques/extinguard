<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtinguisherResource extends JsonResource
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
            'placement_id' => $this->placement_id,
            'serial_number' => $this->serial_number,
            'type' => $this->whenLoaded('type', $this->type?->name),
            'brand' => $this->whenLoaded('brand', $this->brand?->name),
            'supplier' => $this->whenLoaded('supplier', $this->supplier?->name),
            'capacity' => $this->capacity,
            'capacity_unit' => $this->capacity_unit,
            'extinguishing_capacity' => $this->extinguishing_capacity,
            'maintenance_seal' => $this->maintenance_seal,
            'status' => $this->status?->value,
            'next_refill_date' => $this->next_refill_date?->format('Y-m-d'),
            'next_maintenance_year' => $this->next_maintenance_year,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
