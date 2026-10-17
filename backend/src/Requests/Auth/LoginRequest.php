<?php

namespace App\Requests\Auth;

use App\Validation\FormRequest;

class LoginRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'email'       => 'required|email|max:255',
            'password'    => 'required',
        ];
    }

}