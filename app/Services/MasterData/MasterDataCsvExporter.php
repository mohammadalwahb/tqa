<?php

namespace App\Services\MasterData;

use App\Enums\StaffLookupField;
use App\Models\College;
use App\Models\Department;
use App\Models\StaffLookupOption;
use App\Models\StaffStatus;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataCsvExporter
{
    public function colleges(): StreamedResponse
    {
        return $this->stream('colleges.csv', [
            ['name_en', 'name_ku', 'code', 'description', 'is_active'],
        ], function ($write) {
            College::query()->orderBy('name_en')->each(function (College $college) use ($write) {
                $write([
                    $college->name_en,
                    $college->name_ku,
                    $college->code,
                    $college->description,
                    $college->is_active ? '1' : '0',
                ]);
            });
        });
    }

    public function departments(): StreamedResponse
    {
        return $this->stream('departments.csv', [
            ['college_name_en', 'name_en', 'name_ku', 'code', 'description', 'is_active'],
        ], function ($write) {
            Department::query()
                ->with('college')
                ->orderBy('name_en')
                ->each(function (Department $department) use ($write) {
                    $write([
                        $department->college?->name_en,
                        $department->name_en,
                        $department->name_ku,
                        $department->code,
                        $department->description,
                        $department->is_active ? '1' : '0',
                    ]);
                });
        });
    }

    public function staffFieldOptions(): StreamedResponse
    {
        return $this->stream('staff_field_options.csv', [
            ['category', 'name', 'color', 'is_active'],
        ], function ($write) {
            StaffStatus::query()->orderBy('name')->each(function (StaffStatus $status) use ($write) {
                $write(['status', $status->name, $status->color, $status->is_active ? '1' : '0']);
            });

            foreach (StaffLookupField::casesList() as $field) {
                StaffLookupOption::query()
                    ->forField($field)
                    ->orderBy('name')
                    ->each(function (StaffLookupOption $option) use ($write, $field) {
                        $write([$field->value, $option->name, '', $option->is_active ? '1' : '0']);
                    });
            }
        });
    }

    /**
     * @param  list<list<string>>  $headerRows
     * @param  callable(callable): void  $writer
     */
    private function stream(string $filename, array $headerRows, callable $writer): StreamedResponse
    {
        return response()->stream(function () use ($headerRows, $writer) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            foreach ($headerRows as $header) {
                fputcsv($out, $header);
            }

            $write = fn (array $row) => fputcsv($out, $row);
            $writer($write);

            fclose($out);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
