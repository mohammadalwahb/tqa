<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE committee_members MODIFY member_role ENUM(
            'quality_college_coordinator',
            'same_department_member',
            'other_department_member',
            'dean',
            'head_of_department',
            'head_other_department',
            'quality_department_coordinator'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE committee_members MODIFY member_role ENUM(
            'quality_college_coordinator',
            'same_department_member',
            'other_department_member',
            'dean',
            'head_other_department',
            'quality_department_coordinator'
        ) NOT NULL");
    }
};
