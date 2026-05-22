<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('college_id')->constrained('colleges')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('staff_status_id')->nullable()->constrained('staff_statuses')->nullOnDelete()->cascadeOnUpdate();

            $table->string('full_name_en');
            $table->string('full_name_ku')->nullable();
            $table->string('email')->unique();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('age')->nullable();

            $table->string('employee_type')->nullable();
            $table->string('qualification')->nullable();
            $table->string('academic_title')->nullable();
            $table->string('position')->nullable();

            $table->boolean('is_teaching_staff')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['college_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};
