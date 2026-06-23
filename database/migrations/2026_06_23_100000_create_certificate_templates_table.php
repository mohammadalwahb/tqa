<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_form_id')->constrained()->cascadeOnDelete();
            $table->string('background_path')->nullable();
            $table->json('layout')->nullable();
            $table->unsignedSmallInteger('canvas_width')->default(1123);
            $table->unsignedSmallInteger('canvas_height')->default(794);
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('evaluation_period_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
