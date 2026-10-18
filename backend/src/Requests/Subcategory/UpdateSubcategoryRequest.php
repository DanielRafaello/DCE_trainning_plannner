<?php

namespace App\Requests\Subcategory;

use App\Domain\Subcategory;
use App\Validation\FormRequest;

/**
 * PATCH /subcategories/{id} – both fields are optional, a missing one keeps the current value.
 * user_id comes from the JWT and id from the route, never from the body.
 */
class UpdateSubcategoryRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'name'  => 'nullable|min:' . Subcategory::NAME_MIN_LENGTH . '|max:' . Subcategory::NAME_MAX_LENGTH,
            'color' => 'nullable|regex:' . Subcategory::COLOR_PATTERN,
        ];
    }
}
