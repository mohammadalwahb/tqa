<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_lookup_options', function (Blueprint $table) {
            $table->dropUnique(['field', 'name']);
        });

        Schema::table('staff_statuses', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }

    public function down(): void
    {
        Schema::table('staff_lookup_options', function (Blueprint $table) {
            $table->unique(['field', 'name']);
        });

        Schema::table('staff_statuses', function (Blueprint $table) {
            $table->unique(['name']);
        });
    }
};
