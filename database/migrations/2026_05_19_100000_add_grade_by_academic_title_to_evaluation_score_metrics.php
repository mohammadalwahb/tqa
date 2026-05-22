<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_score_metrics', function (Blueprint $table) {
            $table->boolean('grade_by_academic_title')->default(false)->after('show_in_reports');
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_score_metrics', function (Blueprint $table) {
            $table->dropColumn('grade_by_academic_title');
        });
    }
};
