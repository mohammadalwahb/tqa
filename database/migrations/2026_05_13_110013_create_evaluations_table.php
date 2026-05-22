<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained('committees')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('evaluation_form_id')->constrained('evaluation_forms')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('evaluation_period_id')->constrained('evaluation_periods')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('evaluator_user_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('evaluatee_staff_id')->constrained('staff_members')->cascadeOnUpdate()->restrictOnDelete();
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->decimal('total_score', 5, 2)->nullable();
            $table->unsignedInteger('rated_questions_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['committee_id', 'evaluator_user_id', 'evaluatee_staff_id', 'evaluation_period_id'],
                'evaluations_unique'
            );
            $table->index(['evaluation_period_id', 'evaluatee_staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
