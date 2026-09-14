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
        Schema::create('marking_scales', function (Blueprint $table) {
            // Core Columns
            $table->id(); // Auto-incrementing primary key
            $table->decimal('syear', 4, 0); // School year, e.g., 2024
            $table->unsignedBigInteger('school_id'); // School-specific scale
            $table->string('name', 50); // Scale name, e.g., "Standard Grading Scale"
            $table->decimal('min_score', 5, 2); // Minimum score for this grade (e.g., 90.00)
            $table->decimal('max_score', 5, 2); // Maximum score for this grade (e.g., 100.00)
            $table->string('letter_grade', 2); // Corresponding letter grade (e.g., 'A')
            $table->string('comment', 255)->nullable(); // Optional description or notes
            $table->decimal('weight', 5, 2)->default(1.00); // Optional weight for calculations (e.g., 4.0 for GPA if needed later)
            $table->unsignedBigInteger('created_by')->nullable(); // Staff who defined the scale

            // Timestamps
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['school_id', 'syear'])
                ->references(['id', 'syear'])
                ->on('schools')
                ->onUpdate('no action')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('staff_id')
                ->on('staff')
                ->onUpdate('no action')
                ->onDelete('set null');

            // Unique Constraint
            $table->unique(
                ['school_id', 'syear', 'min_score', 'max_score'],
                'marking_scales_unique'
            );

            // Indexes
            $table->index(['school_id', 'syear'], 'marking_scales_school');
            $table->index('letter_grade', 'marking_scales_letter');

            // Engine
            $table->engine = 'InnoDB';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marking_scales');
    }
};
