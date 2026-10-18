<?php

namespace App\Requests\Admin;

use App\Domain\Role;
use App\Validation\FormRequest;

/**
 * PATCH /admin/users/{id} – { "role"?, "blocked"? }, at least one of them (checked in the action).
 * `blocked` is a JSON boolean; the validator sees true as "1" and false as "" (= skipped as empty).
 */
class UpdateUserRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'role'    => 'nullable|in:' . implode(',', array_column(Role::cases(), 'value')),
            'blocked' => 'nullable|in:1,0',
        ];
    }
}
