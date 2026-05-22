<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_lookup_options', function (Blueprint $table) {
            $table->id();
            $table->string('field', 40);
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['field', 'name']);
            $table->index(['field', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_lookup_options');
    }
};
