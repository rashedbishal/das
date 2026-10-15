<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BangladeshiPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = PhoneNumber::normalizeBangladeshi(is_string($value) ? $value : null);

        if (! PhoneNumber::isValidBangladeshi($normalized)) {
            $fail('The :attribute must be a valid Bangladeshi mobile number (01XXXXXXXXX).');
        }
    }
}
