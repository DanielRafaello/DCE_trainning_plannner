<?php

namespace App\Requests\User;

use App\Domain\User;
use App\Validation\FormRequest;

/**
 * PATCH /me – every field is optional, a missing one keeps the current value.
 * Whether the email is free and the timezone is a valid IANA id is checked in UserService.
 */
class UpdateProfileRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'name'     => 'nullable|min:' . User::NAME_MIN_LENGTH . '|max:' . User::NAME_MAX_LENGTH,
            'email'    => 'nullable|email|max:' . User::EMAIL_MAX_LENGTH,
            'timezone' => 'nullable',
        ];
    }
}
