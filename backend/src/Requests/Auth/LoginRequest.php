<?php

namespace App\Requests\Auth;

use App\Domain\User;
use App\Validation\FormRequest;

class LoginRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'email'       => 'required|email|max:' . User::EMAIL_MAX_LENGTH,
            'password'    => 'required',
        ];
    }

}