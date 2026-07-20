<?php

namespace Botble\RealEstate\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'star' => $this->star,
            'comment' => $this->comment,
            'status' => $this->status,
            'reviewable_id' => $this->reviewable_id,
            'reviewable_type' => $this->reviewable_type,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Author information
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'first_name' => $this->author->first_name,
                    'last_name' => $this->author->last_name,
                    'display_name' => $this->author->name,
                    'avatar_url' => $this->author->avatar_url,
                ];
            }),
        ];
    }
}
