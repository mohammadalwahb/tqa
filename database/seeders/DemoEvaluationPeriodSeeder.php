<?php

namespace Database\Seeders;

use App\Models\EvaluationPeriod;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoEvaluationPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $academicYear = (string) config('tqa.current_academic_year', '2025-2026');

        EvaluationPeriod::firstOrCreate(
            ['name' => 'Academic Year ' . $academicYear],
            [
                'academic_year' => $academicYear,
                'start_date'    => Carbon::now()->startOfMonth(),
                'end_date'      => Carbon::now()->addMonths(2)->endOfMonth(),
                'is_active'     => true,
                'description'   => 'Current evaluation window for teaching staff.',
            ]
        );
    }
}
