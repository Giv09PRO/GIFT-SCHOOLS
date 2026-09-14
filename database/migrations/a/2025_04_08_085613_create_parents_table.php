<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('parents', function (Blueprint $table) {
            // Core Columns
            $table->id(); // Auto-incrementing primary key
            $table->string('first_name', 50); // Parent's first name
            $table->string('last_name', 50); // Parent's last name
            $table->string('middle_name', 50)->nullable(); // Parent's middle name
            $table->string('name_suffix', 3)->nullable(); // Parent's name suffix
            $table->string('gender', 10); // Parent's gender
            $table->string('name_prefix', 10)->nullable(); // Parent's name prefix (e.g., Mr., Mrs.))
            $table->string('email', 255)->nullable()->unique(); // Contact email
            $table->string('phone', 30)->nullable(); // Contact phone number
            $table->string('username', 100)->nullable()->unique(); // For authentication
            $table->string('password', 106)->nullable(); // Hashed password for login
            $table->datetime('last_login')->nullable(); // Track last login
            $table->integer('failed_login')->nullable(); // Failed login attempts

            // Timestamps
            $table->timestamps();

        });

        // Create pivot table for parent-student relationship
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('student_id');
            $table->string('relationship', 50)->nullable(); // e.g., 'Mother', 'Father', 'Guardian'
            $table->timestamps();

            // Foreign Keys
            $table->foreign('parent_id')
                ->references('id')
                ->on('parents')
                ->onDelete('cascade');
            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');

            // Unique Constraint
            $table->unique(['parent_id', 'student_id'], 'parent_student_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_student');
        Schema::dropIfExists('parents');
    }
};
