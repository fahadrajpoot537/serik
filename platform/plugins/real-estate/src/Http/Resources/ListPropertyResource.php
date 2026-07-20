<?php

namespace Botble\RealEstate\Http\Resources;

use Botble\Media\Facades\RvMedia;
use Illuminate\Http\Resources\Json\JsonResource;

class ListPropertyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'location' => $this->location,
            'images' => collect($this->images)->map(function ($image) {
                return RvMedia::getImageUrl($image);
            })->toArray(),
            'type' => $this->type,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'number_bedroom' => $this->number_bedroom,
            'number_bathroom' => $this->number_bathroom,
            'number_floor' => $this->number_floor,
            'square' => $this->square,
            'price' => $this->price,
            'price_formatted' => $this->price_format,
            'currency_id' => $this->currency_id,
            'currency' => $this->whenLoaded('currency', function () {
                return $this->currency ? [
                    'id' => $this->currency->id,
                    'title' => $this->currency->title,
                    'symbol' => $this->currency->symbol,
                    'is_prefix_symbol' => $this->currency->is_prefix_symbol,
                    'decimals' => $this->currency->decimals,
                    'exchange_rate' => $this->currency->exchange_rate,
                    'is_default' => $this->currency->is_default,
                ] : null;
            }),
            'period' => $this->period,
            'city_id' => $this->city_id,
            'state_id' => $this->state_id,
            'country_id' => $this->country_id,
            'city' => $this->whenLoaded('city', function () {
                return [
                    'id' => $this->city->id,
                    'name' => $this->city->name,
                ];
            }),
            'state' => $this->whenLoaded('state', function () {
                return [
                    'id' => $this->state->id,
                    'name' => $this->state->name,
                ];
            }),
            'country' => $this->whenLoaded('country', function () {
                return [
                    'id' => $this->country->id,
                    'name' => $this->country->name,
                ];
            }),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'zip_code' => $this->zip_code,
            'unique_id' => $this->unique_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'url' => $this->url,
            'slug' => $this->slug,

            // Basic relationships for listing
            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'name' => $this->project->name,
                    'slug' => $this->project->slug,
                ];
            }),
            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'first_name' => $this->author->first_name,
                    'last_name' => $this->author->last_name,
                    'display_name' => $this->author->name,
                    'avatar_url' => $this->author->avatar_url,
                ];
            }),
            'features_count' => $this->whenLoaded('features', function () {
                return $this->features->count();
            }),
            'facilities_count' => $this->whenLoaded('facilities', function () {
                return $this->facilities->count();
            }),
        ];
    }
}
