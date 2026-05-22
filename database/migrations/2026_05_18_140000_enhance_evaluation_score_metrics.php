<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_score_metrics', function (Blueprint $table) {
            $table->boolean('show_in_reports')->default(true)->after('operation');
        });

        Schema::create('evaluation_score_metric_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_score_metric_id');
            $table->string('label', 20);
            $table->decimal('min_value', 10, 2);
            $table->decimal('max_value', 10, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('evaluation_score_metric_id', 'esmg_metric_fk')
                ->references('id')
                ->on('evaluation_score_metrics')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->index(['evaluation_score_metric_id', 'sort_order'], 'esmg_metric_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_score_metric_grades');

        Schema::table('evaluation_score_metrics', function (Blueprint $table) {
            $table->dropColumn('show_in_reports');
        });
    }
};
