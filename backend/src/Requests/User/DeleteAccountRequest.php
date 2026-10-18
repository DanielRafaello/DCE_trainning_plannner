<?php

namespace App\Requests\User;

use App\Validation\FormRequest;

/**
 * DELETE /me – the account is deleted only with the current password.
 */
class DeleteAccountRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'password' => 'required',
        ];
    }
}
