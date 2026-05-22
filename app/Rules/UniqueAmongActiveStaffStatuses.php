<?php

namespace App\Rules;

use App\Models\StaffStatus;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueAmongActiveStaffStatuses implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = trim((string) $value);
        if ($name === '') {
            return;
        }

        $existing = StaffStatus::findActiveByNameInsensitive($name, $this->ignoreId);

        if ($existing) {
            $hint = $existing->is_active
                ? 'It is already on the status list below — edit that row instead.'
                : 'It exists but is marked inactive — edit or delete that row first.';

            $fail("“{$existing->name}” cannot be added again. {$hint}");
        }
    }
}
