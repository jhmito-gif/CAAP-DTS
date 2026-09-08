<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            Password::min(8)  // Must be at least 8 characters
                ->letters()     // Must contain at least one letter
                ->mixedCase()   // Must contain both uppercase & lowercase
                ->numbers()     // Must contain at least one number
                ->symbols(),    // Must contain at least one special character
            'confirmed',        // Must match password_confirmation
        ];
    }
}
