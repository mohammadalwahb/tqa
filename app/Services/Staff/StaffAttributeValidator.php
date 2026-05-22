<?php

namespace App\Services\Staff;

use App\Enums\StaffLookupField;
use App\Models\StaffLookupOption;
use App\Models\StaffStatus;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StaffAttributeValidator
{
    /** @var array<string, Collection<int, string>>|null */
    private static ?array $lookupCache = null;

    /** @var Collection<int, string>|null */
    private static ?Collection $statusNamesCache = null;

    /**
     * @return list<string>
     */
    public function allowedLookupNames(StaffLookupField $field): array
    {
        return $this->lookupNames($field)->values()->all();
    }

    /**
     * @return list<string>
     */
    public function allowedStatusNames(): array
    {
        return $this->statusNames()->values()->all();
    }

    public function resolveLookupValue(?string $value, StaffLookupField $field, string $attributeLabel): ?string
    {
        $value = $this->normalize($value);
        if ($value === null) {
            return null;
        }

        $allowed = $this->lookupNames($field);
        if ($allowed->isEmpty()) {
            throw ValidationException::withMessages([
                $field->value => "No {$attributeLabel} values have been configured. Ask a Super Admin to add them first.",
            ]);
        }

        $match = $allowed->first(
            fn (string $name) => mb_strtolower($name) === mb_strtolower($value)
        );

        if ($match === null) {
            throw ValidationException::withMessages([
                $field->value => "Invalid {$attributeLabel}. Allowed values: {$allowed->implode(', ')}.",
            ]);
        }

        return $match;
    }

    public function resolveStatusId(?string $value, string $attributeLabel = 'Status'): ?int
    {
        $value = $this->normalize($value);
        if ($value === null) {
            return null;
        }

        $status = StaffStatus::query()
            ->active()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($value)])
            ->first();

        if (! $status) {
            $allowed = $this->statusNames();
            $message = $allowed->isEmpty()
                ? "No {$attributeLabel} values have been configured. Ask a Super Admin to add them under Staff Field Options."
                : "Invalid {$attributeLabel}. Allowed values: {$allowed->implode(', ')}.";

            throw ValidationException::withMessages(['status' => $message]);
        }

        return $status->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rulesForStaffForm(?int $staffId = null): array
    {
        $lookupRules = fn (StaffLookupField $field) => $this->inRule(
            $field->value,
            $this->allowedLookupNames($field)
        );

        return [
            'employee_type'  => $lookupRules(StaffLookupField::EmployeeType),
            'qualification'  => $lookupRules(StaffLookupField::Qualification),
            'academic_title' => $lookupRules(StaffLookupField::AcademicTitle),
            'position'       => $lookupRules(StaffLookupField::Position),
            'staff_status_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $exists = StaffStatus::query()->active()->whereKey($value)->exists();
                    if (! $exists) {
                        $fail('The selected status is not valid or is inactive.');
                    }
                },
            ],
        ];
    }

    /**
     * @param  list<string>  $allowed
     * @return list<string|object>
     */
    private function inRule(string $attribute, array $allowed): array
    {
        if ($allowed === []) {
            return ['nullable', 'prohibited'];
        }

        return ['nullable', 'string', 'max:120', \Illuminate\Validation\Rule::in($allowed)];
    }

    private function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function lookupNames(StaffLookupField $field): Collection
    {
        self::$lookupCache ??= [];

        if (! isset(self::$lookupCache[$field->value])) {
            self::$lookupCache[$field->value] = StaffLookupOption::query()
                ->forField($field)
                ->active()
                ->orderBy('name')
                ->pluck('name');
        }

        return self::$lookupCache[$field->value];
    }

    private function statusNames(): Collection
    {
        return self::$statusNamesCache ??= StaffStatus::query()
            ->active()
            ->orderBy('name')
            ->pluck('name');
    }

    public static function flushCache(): void
    {
        self::$lookupCache = null;
        self::$statusNamesCache = null;
    }
}
