<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committees', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['local', 'hd']);
            $table->string('name')->nullable();
            $table->foreignId('college_id')->constrained('colleges')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('evaluation_period_id')->constrained('evaluation_periods')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('evaluation_form_id')->nullable()->constrained('evaluation_forms')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'college_id', 'department_id', 'evaluation_period_id'], 'committees_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committees');
    }
};
