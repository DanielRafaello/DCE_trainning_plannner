<?php

namespace App\Requests\Subcategory;

use App\Validation\FormRequest;

class ListSubcategoriesRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'category' => 'nullable|integer',
        ];
    }
}
