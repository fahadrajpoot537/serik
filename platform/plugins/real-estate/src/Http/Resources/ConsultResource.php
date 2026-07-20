<?php

namespace Botble\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConsultResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'content' => $this->content,
            'property_id' => $this->property_id,
            'project_id' => $this->project_id,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
