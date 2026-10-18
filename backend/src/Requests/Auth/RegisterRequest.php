<?php

namespace App\Requests\Auth;

use App\Domain\User;
use App\Validation\FormRequest;

class RegisterRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'name'        => 'required|min:' . User::NAME_MIN_LENGTH . '|max:' . User::NAME_MAX_LENGTH,
            'email'       => 'required|email|max:' . User::EMAIL_MAX_LENGTH,
            'password'    => 'required|min:' . User::PASSWORD_MIN_LENGTH . '|confirmed',
        ];
    }
}
