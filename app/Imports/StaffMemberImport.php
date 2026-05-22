<?php

namespace App\Imports;

use App\Enums\StaffLookupField;
use App\Models\College;
use App\Models\Department;
use App\Models\StaffMember;
use App\Services\Staff\StaffAttributeValidator;
use App\Services\Staff\StaffOrganizationalRoleAssigner;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Support\Utf8Helper;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StaffMemberImport implements ToCollection, WithHeadingRow, WithChunkReading, WithCustomCsvSettings, SkipsEmptyRows, SkipsOnError, SkipsOnFailure
{
    use Importable;

    public int $created = 0;
    public int $updated = 0;
    public array $errors = [];

    /** @var array<int, string> */
    private array $allowedDomains;

    public function __construct(
        private readonly StaffAttributeValidator $attributes,
        private readonly StaffOrganizationalRoleAssigner $orgRoles,
    ) {
        $this->allowedDomains = collect(config('tqa.allowed_email_domains', []))
            ->map(fn ($d) => mb_strtolower(trim($d)))
            ->all();
    }

    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'UTF-8',
        ];
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2;
            $data = $this->normalizeKeys($row->toArray());

            try {
                $this->processRow($data, $rowNumber);
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNumber}: {$e->getMessage()}";
            }
        }
    }

    private function processRow(array $row, int $rowNumber): void
    {
        $email = mb_strtolower(trim((string) (
            $row['institutional_e_mail']
            ?? $row['institutional_email']
            ?? $row['email']
            ?? ''
        )));
        if ($email === '') {
            throw new \RuntimeException('Email is required.');
        }

        $domain = Str::after($email, '@');
        if (! in_array(mb_strtolower($domain), $this->allowedDomains, true)) {
            throw new \RuntimeException("Email domain @{$domain} is not allowed.");
        }

        $collegeName    = trim((string) ($row['college'] ?? ''));
        $departmentName = trim((string) ($row['department'] ?? ''));
        if ($collegeName === '' || $departmentName === '') {
            throw new \RuntimeException('College and department are required.');
        }

        $college = College::firstOrCreate(['name_en' => $collegeName], ['is_active' => true]);
        $department = Department::firstOrCreate(
            ['college_id' => $college->id, 'name_en' => $departmentName],
            ['is_active' => true]
        );

        $dob = $this->parseDate($row['date_of_birth'] ?? null);
        $age = is_numeric($row['age'] ?? null) ? (int) $row['age'] : ($dob?->age);

        $payload = [
            'full_name_en'      => trim((string) (
                $row['full_name_first_second_third_in_english']
                ?? $row['full_name_in_english']
                ?? $row['full_name_en']
                ?? $row['name']
                ?? ''
            )),
            'full_name_ku'      => Utf8Helper::toUtf8((string) ($row['full_name_in_kurdish'] ?? $row['full_name_ku'] ?? '')),
            'gender'            => $this->normalizeGender($row['gender'] ?? null),
            'date_of_birth'     => $dob?->toDateString(),
            'age'               => $age,
            'employee_type'     => $this->resolveLookup($row['employee_type'] ?? null, StaffLookupField::EmployeeType, 'Employee type'),
            'college_id'        => $college->id,
            'department_id'     => $department->id,
            'qualification'     => $this->resolveLookup($row['qualification'] ?? null, StaffLookupField::Qualification, 'Qualification'),
            'academic_title'    => $this->resolveLookup($row['academic_title'] ?? null, StaffLookupField::AcademicTitle, 'Academic title'),
            'position'          => $this->resolveLookup($row['position'] ?? null, StaffLookupField::Position, 'Position'),
            'staff_status_id'   => $this->resolveStatus($row['status'] ?? null),
            'is_teaching_staff' => true,
            'is_active'         => true,
        ];

        if ($payload['full_name_en'] === '') {
            throw new \RuntimeException('Full Name (English) is required.');
        }

        $staff = StaffMember::withTrashed()->where('email', $email)->first();

        if ($staff) {
            if ($staff->trashed()) {
                $staff->restore();
            }
            $staff->fill($payload)->save();
            $this->updated++;
        } else {
            $staff = StaffMember::create(array_merge($payload, ['email' => $email]));
            $this->created++;
        }

        $this->orgRoles->assignFromPosition(
            $staff->fresh(),
            $payload['position'],
            $college,
            $department
        );
    }

    private function normalizeKeys(array $row): array
    {
        $out = [];
        foreach ($row as $k => $v) {
            $key = Str::of((string) $k)
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '_')
                ->trim('_')
                ->__toString();
            $out[$key] = $v;
        }
        return $out;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value));
        }
        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeGender(mixed $value): ?string
    {
        $v = mb_strtolower((string) $value);
        return match (true) {
            in_array($v, ['m', 'male', 'man'], true)   => 'male',
            in_array($v, ['f', 'female', 'woman'], true) => 'female',
            default => null,
        };
    }

    private function resolveLookup(mixed $value, StaffLookupField $field, string $label): ?string
    {
        try {
            return $this->attributes->resolveLookupValue($this->str($value), $field, $label);
        } catch (ValidationException $e) {
            throw new \RuntimeException($this->firstValidationMessage($e));
        }
    }

    private function resolveStatus(mixed $value): ?int
    {
        try {
            return $this->attributes->resolveStatusId($this->str($value));
        } catch (ValidationException $e) {
            throw new \RuntimeException($this->firstValidationMessage($e));
        }
    }

    private function firstValidationMessage(ValidationException $e): string
    {
        return collect($e->errors())->flatten()->first() ?? 'Invalid value.';
    }

    private function str(mixed $value): ?string
    {
        $v = trim((string) $value);
        return $v === '' ? null : $v;
    }

    public function onError(\Throwable $e): void
    {
        $this->errors[] = $e->getMessage();
    }

    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->errors[] = 'Row ' . $failure->row() . ': ' . implode(', ', $failure->errors());
        }
    }

    public function chunkSize(): int
    {
        return 200;
    }
}
