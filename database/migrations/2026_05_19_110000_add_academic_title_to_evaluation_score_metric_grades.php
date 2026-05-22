<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_score_metric_grades', function (Blueprint $table) {
            $table->string('academic_title', 120)->nullable()->after('evaluation_score_metric_id');
            $table->unsignedSmallInteger('title_sort_order')->default(0)->after('academic_title');
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_score_metric_grades', function (Blueprint $table) {
            $table->dropColumn(['academic_title', 'title_sort_order']);
        });
    }
};
