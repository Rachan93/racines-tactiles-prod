<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Rules shared by registration, password update and password reset.
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',

            Password::min(10)
                ->mixedCase()
                ->numbers(),

            'confirmed',
        ];
    }
}
