<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\UPIBlockList;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class UPI implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check regex
        if (in_array(preg_match('/^[a-zA-Z0-9.\-]{2,256}@[a-zA-Z]{2,64}$/', (string) $value), [0, false], true)) {
            $fail('Please enter a valid UPI ID.');
        }

        // Check Blocklist
        if (UPIBlockList::isUpiBlocked($value)) {
            $fail('This UPI is not allowed.');
        }
    }
}
