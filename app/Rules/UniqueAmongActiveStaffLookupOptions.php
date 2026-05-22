<?php

namespace App\Rules;

use App\Enums\StaffLookupField;
use App\Models\StaffLookupOption;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueAmongActiveStaffLookupOptions implements ValidationRule
{
    public function __construct(
        private readonly ?string $field,
        private readonly ?int $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $field = StaffLookupField::tryFrom((string) $this->field);
        if (! $field) {
            return;
        }

        $name = trim((string) $value);
        if ($name === '') {
            return;
        }

        $existing = StaffLookupOption::findActiveByNameInsensitive($field, $name, $this->ignoreId);

        if ($existing) {
            $hint = $existing->is_active
                ? 'It is already listed for this field — edit that row instead.'
                : 'It exists but is inactive — edit or delete that row first.';

            $fail("“{$existing->name}” cannot be added again. {$hint}");
        }
    }
}
