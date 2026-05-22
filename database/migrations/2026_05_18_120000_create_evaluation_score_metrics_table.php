<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('evaluation_score_metrics')) {
            Schema::create('evaluation_score_metrics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('evaluation_form_id')->constrained('evaluation_forms')->cascadeOnDelete()->cascadeOnUpdate();
                $table->string('name');
                $table->enum('operation', ['sum', 'average'])->default('sum');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('evaluation_score_metric_question')) {
            $this->createPivotTable();

            return;
        }

        $this->addPivotForeignKeysIfMissing();
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_score_metric_question');
        Schema::dropIfExists('evaluation_score_metrics');
    }

    private function createPivotTable(): void
    {
        Schema::create('evaluation_score_metric_question', function (Blueprint $table) {
            $table->foreignId('evaluation_score_metric_id');
            $table->foreignId('evaluation_question_id');
            $table->primary(['evaluation_score_metric_id', 'evaluation_question_id'], 'esmq_primary');

            $table->foreign('evaluation_score_metric_id', 'esmq_metric_fk')
                ->references('id')
                ->on('evaluation_score_metrics')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreign('evaluation_question_id', 'esmq_question_fk')
                ->references('id')
                ->on('evaluation_questions')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    private function addPivotForeignKeysIfMissing(): void
    {
        $connection = Schema::getConnection()->getDriverName();
        if ($connection !== 'mysql') {
            return;
        }

        $existing = collect(Schema::getConnection()->select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            ['evaluation_score_metric_question', 'FOREIGN KEY']
        ))->pluck('CONSTRAINT_NAME');

        Schema::table('evaluation_score_metric_question', function (Blueprint $table) use ($existing) {
            if (! $existing->contains('esmq_metric_fk')) {
                $table->foreign('evaluation_score_metric_id', 'esmq_metric_fk')
                    ->references('id')
                    ->on('evaluation_score_metrics')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            }
            if (! $existing->contains('esmq_question_fk')) {
                $table->foreign('evaluation_question_id', 'esmq_question_fk')
                    ->references('id')
                    ->on('evaluation_questions')
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
            }
        });
    }
};
