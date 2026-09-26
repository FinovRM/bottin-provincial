<?php

namespace App\Rules;

use App\Models\Responsable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Keeps "Responsable du bottin" out of member roles and of the roles an
 * organization sets for its children: responsables have their own section.
 */
class NotReservedRole implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && Responsable::isReservedRole($value)) {
            $fail('Le rôle « '.Responsable::ROLE.' » est réservé à la section des responsables.');
        }
    }
}
