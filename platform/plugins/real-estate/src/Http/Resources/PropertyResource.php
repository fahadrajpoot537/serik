<?php

namespace Botble\RealEstate\Http\Resources;

use Botble\Media\Facades\RvMedia;
use Botble\Shortcode\Facades\Shortcode;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'content' => Shortcode::compile((string) $this->content, true)->toHtml(),
            'location' => $this->location,
            'images' => collect($this->images)->map(function ($image) {
                return RvMedia::getImageUrl($image);
            })->toArray(),
            'type' => $this->type,
            'status' => $this->status,
            'moderation_status' => $this->moderation_status,
            'is_featured' => $this->is_featured,
            'featured_priority' => $this->featured_priority,
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
            'floor_plans' => collect($this->floor_plans)->map(function ($plan) {
                if (isset($plan['image']['value'])) {
                    $plan['image']['value'] = RvMedia::getImageUrl($plan['image']['value']);
                }

                return $plan;
            })->toArray(),
            'expire_date' => $this->expire_date?->toISOString(),
            'auto_renew' => $this->auto_renew,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'url' => $this->url,
            'slug' => $this->slug,

            // Relationships
            'project' => $this->whenLoaded('project', function () {
                return new ProjectResource($this->project);
            }),
            'author' => $this->whenLoaded('author', function () {
                return new AccountResource($this->author);
            }),
            'features' => $this->whenLoaded('features', function () {
                return FeatureResource::collection($this->features);
            }),
            'facilities' => $this->whenLoaded('facilities', function () {
                return FacilityResource::collection($this->facilities);
            }),
            'categories' => $this->whenLoaded('categories', function () {
                return CategoryResource::collection($this->categories);
            }),
        ];
    }
}
