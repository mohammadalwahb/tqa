<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('staff_member_id')
                ->nullable()
                ->after('avatar_url')
                ->constrained('staff_members')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('college_id')
                ->nullable()
                ->after('staff_member_id')
                ->constrained('colleges')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('colleges', function (Blueprint $table) {
            $table->foreignId('dean_staff_id')
                ->nullable()
                ->after('description')
                ->constrained('staff_members')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->foreignId('head_staff_id')
                ->nullable()
                ->after('description')
                ->constrained('staff_members')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('quality_coordinator_staff_id')
                ->nullable()
                ->after('head_staff_id')
                ->constrained('staff_members')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quality_coordinator_staff_id');
            $table->dropConstrainedForeignId('head_staff_id');
        });

        Schema::table('colleges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dean_staff_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('college_id');
            $table->dropConstrainedForeignId('staff_member_id');
        });
    }
};
