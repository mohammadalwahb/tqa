<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained('committees')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('staff_member_id')->nullable()->constrained('staff_members')->nullOnDelete()->cascadeOnUpdate();
            $table->enum('member_role', [
                'quality_college_coordinator',
                'same_department_member',
                'other_department_member',
                'dean',
                'head_other_department',
                'quality_department_coordinator',
            ]);
            $table->foreignId('source_department_id')->nullable()->constrained('departments')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['committee_id', 'staff_member_id'], 'cm_unique_staff');
            $table->index(['committee_id', 'member_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_members');
    }
};
