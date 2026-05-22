<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $from = '2026-2027';
        $to = (string) config('tqa.current_academic_year', '2025-2026');

        DB::table('evaluation_periods')
            ->where('academic_year', $from)
            ->update(['academic_year' => $to]);

        DB::table('evaluation_periods')
            ->where('name', 'Academic Year ' . $from)
            ->update(['name' => 'Academic Year ' . $to]);
    }

    public function down(): void
    {
        $from = (string) config('tqa.current_academic_year', '2025-2026');
        $to = '2026-2027';

        DB::table('evaluation_periods')
            ->where('academic_year', $from)
            ->update(['academic_year' => $to]);

        DB::table('evaluation_periods')
            ->where('name', 'Academic Year ' . $from)
            ->update(['name' => 'Academic Year ' . $to]);
    }
};
