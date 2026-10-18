<?php

namespace App\Requests\Subcategory;

use App\Domain\Subcategory;
use App\Validation\FormRequest;

/**
 * POST /subcategories. Whether the category exists and whether the name is taken
 * is checked in SubcategoryService (needs the database).
 */
class CreateSubcategoryRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'category_id' => 'required|integer',
            'name'        => 'required|min:' . Subcategory::NAME_MIN_LENGTH . '|max:' . Subcategory::NAME_MAX_LENGTH,
            'color'       => 'nullable|regex:' . Subcategory::COLOR_PATTERN,
        ];
    }
}
