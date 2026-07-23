<?php

namespace App\Casts;

use Botble\RealEstate\Enums\PropertyStatusEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Maps empty/invalid re_properties.status values to draft without enum error logs.
 */
class SafePropertyStatusEnum implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): PropertyStatusEnum
    {
        if ($value instanceof PropertyStatusEnum) {
            return $value;
        }

        $raw = is_string($value) ? trim($value) : $value;
        if ($raw === null || $raw === '' || ! PropertyStatusEnum::isValid($raw)) {
            return PropertyStatusEnum::DRAFT();
        }

        return (new PropertyStatusEnum())->make($raw);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof PropertyStatusEnum) {
            $value = $value->getValue();
        }

        $raw = is_string($value) ? trim($value) : $value;
        if ($raw === null || $raw === '' || ! PropertyStatusEnum::isValid($raw)) {
            return PropertyStatusEnum::DRAFT;
        }

        return (string) $raw;
    }
}
