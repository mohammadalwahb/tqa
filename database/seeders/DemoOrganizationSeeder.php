<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DemoOrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            'College of Science' => [
                'code'         => 'SCI',
                'departments'  => ['Mathematics', 'Computer Science', 'Physics'],
            ],
            'College of Engineering' => [
                'code'         => 'ENG',
                'departments'  => ['Civil Engineering', 'Electrical Engineering', 'Mechanical Engineering'],
            ],
            'College of Education' => [
                'code'         => 'EDU',
                'departments'  => ['English', 'Kurdish', 'History'],
            ],
        ];

        foreach ($structure as $name => $data) {
            $college = College::firstOrCreate(
                ['name_en' => $name],
                ['code' => $data['code'], 'is_active' => true]
            );

            foreach ($data['departments'] as $dept) {
                Department::firstOrCreate(
                    ['college_id' => $college->id, 'name_en' => $dept],
                    ['is_active' => true]
                );
            }
        }
    }
}
