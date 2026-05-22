<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_form_id')->constrained('evaluation_forms')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('evaluation_category_id')->nullable()->constrained('evaluation_categories')->nullOnDelete()->cascadeOnUpdate();
            $table->mediumText('text');
            $table->text('help_text')->nullable();
            $table->enum('type', ['rating', 'text', 'number'])->default('rating');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['evaluation_form_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_questions');
    }
};
