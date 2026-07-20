<?php

namespace Botble\RealEstate\Http\Resources;

use Botble\Media\Facades\RvMedia;
use Illuminate\Http\Resources\Json\JsonResource;

class ListProjectResource extends JsonResource
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
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'number_block' => $this->number_block,
            'number_floor' => $this->number_floor,
            'number_flat' => $this->number_flat,
            'date_finish' => $this->date_finish?->toDateString(),
            'date_sell' => $this->date_sell?->toDateString(),
            'price_from' => $this->price_from,
            'price_from_formatted' => $this->price_from_formatted,
            'price_to' => $this->price_to,
            'price_to_formatted' => $this->price_to_formatted,
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
            'investor' => $this->whenLoaded('investor', function () {
                return [
                    'id' => $this->investor->id,
                    'name' => $this->investor->name,
                ];
            }),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
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
            'properties_count' => $this->whenLoaded('properties', function () {
                return $this->properties->count();
            }),
        ];
    }
}
