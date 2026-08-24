<?php

namespace App\Rules;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class AdultDateOfBirth implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $dateOfBirth = CarbonImmutable::createFromFormat('Y-m-d', (string) $value)->startOfDay();
        } catch (\Throwable) {
            return;
        }

        if ($dateOfBirth->isAfter(CarbonImmutable::today()->subYears(18))) {
            $fail('Người thuê phải đủ 18 tuổi.');
        }
    }
}
