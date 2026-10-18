<?php

namespace App\Requests\Admin;

use App\Domain\User;
use App\Services\Admin\AdminUserService;
use App\Validation\FormRequest;

/**
 * GET /admin/users?search=&page=&per_page=
 */
class ListUsersRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'search'   => 'nullable|max:' . User::EMAIL_MAX_LENGTH,
            'page'     => 'nullable|integer', // < 1 is clamped to 1 in AdminUserService
            'per_page' => 'nullable|integer|between:1,' . AdminUserService::MAX_PER_PAGE,
        ];
    }
}
