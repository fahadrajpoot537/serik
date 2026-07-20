<?php

namespace Botble\RealEstate\Http\Requests;

use Botble\RealEstate\Models\Project;
use Botble\RealEstate\Models\Property;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class ReviewRequest extends Request
{
    public function rules(): array
    {
        return [
            'reviewable_type' => Rule::in([Property::class, Project::class]),
            'star' => ['required', 'int', 'min:1', 'max:5'],
            'content' => ['required', 'string', 'min:4', 'max:500'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'reviewable_type' => [
                'description' => 'Type of reviewable entity (Property or Project)',
                'example' => Property::class,
            ],
            'star' => [
                'description' => 'Star rating for the review (1-5)',
                'example' => 5,
            ],
            'content' => [
                'description' => 'Content of the review',
                'example' => 'This is an excellent property with great amenities.',
            ],
        ];
    }
}
