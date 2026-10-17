<?php

declare(strict_types=1);

namespace App\Requests\Auth;

use App\Validation\FormRequest;

/** Shared by /auth/refresh and /auth/logout — both just take the refresh token. */
class RefreshRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'refreshToken' => 'required',
        ];
    }
}
