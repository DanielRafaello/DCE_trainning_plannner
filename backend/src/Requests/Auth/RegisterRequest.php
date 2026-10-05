<?php

namespace App\Requests\Auth;

use App\Validation\FormRequest;

class RegisterRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'name'        => 'required|min:2|max:100',
            'email'       => 'required|email|max:255',
            'password'    => 'required|min:8|confirmed',
        ];
    }
}