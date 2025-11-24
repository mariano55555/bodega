<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Minimum 8 characters
        if (strlen($value) < 8) {
            $fail('La contraseña debe tener al menos 8 caracteres.');

            return;
        }

        // At least one uppercase letter
        if (! preg_match('/[A-Z]/', $value)) {
            $fail('La contraseña debe contener al menos una letra mayúscula.');

            return;
        }

        // At least one lowercase letter
        if (! preg_match('/[a-z]/', $value)) {
            $fail('La contraseña debe contener al menos una letra minúscula.');

            return;
        }

        // At least one number
        if (! preg_match('/[0-9]/', $value)) {
            $fail('La contraseña debe contener al menos un número.');

            return;
        }

        // At least one special character
        if (! preg_match('/[@$!%*?&]/', $value)) {
            $fail('La contraseña debe contener al menos un carácter especial (@$!%*?&).');

            return;
        }

        // Check for common patterns
        if (preg_match('/^(?:password|123456|qwerty)/i', $value)) {
            $fail('La contraseña no puede contener patrones comunes.');

            return;
        }
    }
}
