<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the existing 'students' table
        Schema::table('students', function (Blueprint $table) {
            // Add the 'deleted_at' column needed for soft deletes
            // This column is nullable by default
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Modify the existing 'students' table
        Schema::table('students', function (Blueprint $table) {
            // Remove the 'deleted_at' column if the migration is rolled back
            $table->dropSoftDeletes();
        });
    }
};
