<?php

namespace Botble\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ListAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'description' => $this->description,
            'is_featured' => $this->is_featured,
            'is_public_profile' => $this->is_public_profile,
            'avatar_url' => $this->avatar_url,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'properties_count' => $this->properties_count ?? 0,
            'projects_count' => $this->projects_count ?? 0,
        ];
    }
}
