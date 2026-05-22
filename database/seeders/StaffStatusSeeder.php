<?php

namespace Database\Seeders;

use App\Models\StaffStatus;
use Illuminate\Database\Seeder;

class StaffStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Active',          'color' => 'success'],
            ['name' => 'On Leave',        'color' => 'warning'],
            ['name' => 'On Study Leave',  'color' => 'info'],
            ['name' => 'Sabbatical',      'color' => 'primary'],
            ['name' => 'Retired',         'color' => 'secondary'],
            ['name' => 'Resigned',        'color' => 'dark'],
            ['name' => 'Suspended',       'color' => 'danger'],
        ];

        foreach ($statuses as $s) {
            StaffStatus::firstOrCreate(['name' => $s['name']], $s);
        }
    }
}
