<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_question_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_question_id')
                ->constrained('evaluation_questions')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->unique(['evaluation_question_id', 'role_id'], 'eqr_unique');
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_question_role');
    }
};
