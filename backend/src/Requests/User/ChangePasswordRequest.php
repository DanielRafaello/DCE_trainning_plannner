<?php

namespace App\Requests\User;

use App\Domain\User;
use App\Validation\FormRequest;

/**
 * PUT /me/password – `confirmed` compares `password` with `password_confirm`.
 */
class ChangePasswordRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'current_password' => 'required',
            'password'         => 'required|min:' . User::PASSWORD_MIN_LENGTH . '|confirmed',
        ];
    }
}
