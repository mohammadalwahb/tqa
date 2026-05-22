<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            StaffStatusSeeder::class,
            StaffLookupOptionSeeder::class,
            SuperAdminSeeder::class,
            DemoOrganizationSeeder::class,
            DemoEvaluationPeriodSeeder::class,
            DefaultEvaluationFormSeeder::class,
        ]);
    }
}
