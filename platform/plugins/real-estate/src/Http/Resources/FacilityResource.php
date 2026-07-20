<?php

namespace Botble\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FacilityResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'icon' => $this->icon,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'distance' => $this->whenPivotLoaded('re_facilities_distances', function () {
                return $this->pivot->distance;
            }),
        ];
    }
}
