<?php

namespace App\Services\MasterData;

use App\Enums\StaffLookupField;
use App\Models\College;
use App\Models\Department;
use App\Models\StaffLookupOption;
use App\Models\StaffStatus;
use App\Services\Staff\StaffOptionProvisioningService;
use App\Support\Utf8Helper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MasterDataCsvImporter
{
    public int $created = 0;
    public int $updated = 0;
    public array $errors = [];

    public function __construct(
        private readonly StaffOptionProvisioningService $optionProvisioner,
    ) {}

    public function importColleges(UploadedFile $file): void
    {
        $this->resetCounters();
        $this->eachRow($file, function (array $row) {
            $name = Utf8Helper::toUtf8(trim((string) ($row['name_en'] ?? '')));
            if ($name === null || $name === '') {
                throw new \RuntimeException('name_en is required.');
            }

            $college = College::withTrashed()->where('name_en', $name)->first();
            $payload = [
                'name_en'     => $name,
                'name_ku'     => Utf8Helper::toUtf8($row['name_ku'] ?? null),
                'code'        => $this->nullableString($row['code'] ?? null),
                'description' => $this->nullableString($row['description'] ?? null),
                'is_active'   => $this->toBool($row['is_active'] ?? true),
            ];

            if ($college) {
                if ($college->trashed()) {
                    $college->restore();
                }
                $college->update($payload);
                $this->updated++;
            } else {
                College::create($payload);
                $this->created++;
            }
        });
    }

    public function importDepartments(UploadedFile $file): void
    {
        $this->resetCounters();
        $this->eachRow($file, function (array $row) {
            $collegeName = Utf8Helper::toUtf8(trim((string) ($row['college_name_en'] ?? '')));
            $name        = Utf8Helper::toUtf8(trim((string) ($row['name_en'] ?? '')));

            if ($collegeName === null || $collegeName === '' || $name === null || $name === '') {
                throw new \RuntimeException('college_name_en and name_en are required.');
            }

            $college = College::query()->where('name_en', $collegeName)->first();
            if (! $college) {
                throw new \RuntimeException("College \"{$collegeName}\" was not found.");
            }

            $department = Department::withTrashed()
                ->where('college_id', $college->id)
                ->where('name_en', $name)
                ->first();

            $payload = [
                'college_id'  => $college->id,
                'name_en'     => $name,
                'name_ku'     => Utf8Helper::toUtf8($row['name_ku'] ?? null),
                'code'        => $this->nullableString($row['code'] ?? null),
                'description' => $this->nullableString($row['description'] ?? null),
                'is_active'   => $this->toBool($row['is_active'] ?? true),
            ];

            if ($department) {
                if ($department->trashed()) {
                    $department->restore();
                }
                $department->update($payload);
                $this->updated++;
            } else {
                Department::create($payload);
                $this->created++;
            }
        });
    }

    public function importStaffFieldOptions(UploadedFile $file): void
    {
        $this->resetCounters();
        $this->eachRow($file, function (array $row) {
            $category = mb_strtolower(trim((string) ($row['category'] ?? '')));
            $name     = Utf8Helper::toUtf8(trim((string) ($row['name'] ?? '')));

            if ($category === '' || $name === null || $name === '') {
                throw new \RuntimeException('category and name are required.');
            }

            $isActive = $this->toBool($row['is_active'] ?? true);

            if ($category === 'status') {
                $this->importStatus($name, $row, $isActive);

                return;
            }

            $field = StaffLookupField::tryFrom($category);
            if (! $field) {
                throw new \RuntimeException("Unknown category \"{$category}\".");
            }

            $this->importLookupOption($field, $name, $isActive);
        });
    }

    private function importStatus(string $name, array $row, bool $isActive): void
    {
        $payload = [
            'name'      => $name,
            'color'     => $this->nullableString($row['color'] ?? null) ?? 'secondary',
            'is_active' => $isActive,
        ];

        $existing = StaffStatus::findActiveByNameInsensitive($name);
        if ($existing) {
            $existing->update($payload);
            $this->updated++;

            return;
        }

        $result = $this->optionProvisioner->storeStatus($payload);
        $result['restored'] ? $this->updated++ : $this->created++;
    }

    private function importLookupOption(StaffLookupField $field, string $name, bool $isActive): void
    {
        $existing = StaffLookupOption::findActiveByNameInsensitive($field, $name);
        if ($existing) {
            $existing->update(['is_active' => $isActive]);
            $this->updated++;

            return;
        }

        $result = $this->optionProvisioner->storeLookupOption([
            'field'     => $field->value,
            'name'      => $name,
            'is_active' => $isActive,
        ]);

        $result['restored'] ? $this->updated++ : $this->created++;
    }

    /**
     * @param  callable(array<string, mixed>): void  $callback
     */
    private function eachRow(UploadedFile $file, callable $callback): void
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new \RuntimeException('Could not read the uploaded file.');
        }

        if (fread($handle, 3) !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = null;
        $line   = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $line++;

            if ($header === null) {
                $header = array_map(
                    fn ($cell) => Str::of((string) $cell)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->__toString(),
                    $data
                );
                continue;
            }

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $row = [];
            foreach ($header as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $row[$key] = $data[$index] ?? null;
            }

            try {
                $callback($row);
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$line}: {$e->getMessage()}";
            }
        }

        fclose($handle);
    }

    /**
     * @param  list<string|null>  $data
     */
    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = Utf8Helper::toUtf8(trim((string) $value));

        return $value === null || $value === '' ? null : $value;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(mb_strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y'], true);
    }

    private function resetCounters(): void
    {
        $this->created = 0;
        $this->updated = 0;
        $this->errors  = [];
    }
}
