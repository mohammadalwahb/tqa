<?php

namespace Database\Seeders;

use App\Enums\StaffLookupField;
use App\Models\StaffLookupOption;
use Illuminate\Database\Seeder;

class StaffLookupOptionSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            StaffLookupField::EmployeeType->value => [
                'Permanent',
                'Contract',
                'Part-time',
            ],
            StaffLookupField::Qualification->value => [
                'PhD',
                'MSc',
                'BSc',
                'Diploma',
            ],
            StaffLookupField::AcademicTitle->value => [
                'Professor',
                'Associate Professor',
                'Assistant Professor',
                'Assistant Prof.',
                'Lecturer',
            ],
            StaffLookupField::Position->value => [
                'Lecturer',
                'Head of Department',
                'Dean',
                'Coordinator',
            ],
        ];

        foreach ($options as $field => $names) {
            foreach ($names as $name) {
                StaffLookupOption::firstOrCreate(
                    ['field' => $field, 'name' => $name],
                    ['is_active' => true]
                );
            }
        }
    }
}
