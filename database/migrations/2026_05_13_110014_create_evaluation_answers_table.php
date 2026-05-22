<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('evaluation_question_id')->constrained('evaluation_questions')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedTinyInteger('rating_value')->nullable();
            $table->decimal('number_value', 10, 2)->nullable();
            $table->text('text_value')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_id', 'evaluation_question_id'], 'ea_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_answers');
    }
};
