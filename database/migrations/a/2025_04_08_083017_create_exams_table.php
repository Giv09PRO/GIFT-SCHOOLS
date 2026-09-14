<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExamsTable extends Migration
{
    public function up()
    {
        Schema::create('exams', function (Blueprint $table) {
            // Core Columns
            $table->id(); // Auto-incrementing primary key
            $table->decimal('syear', 4, 0); // School year, e.g., 2024
            $table->unsignedBigInteger('school_id'); // School association
            $table->unsignedBigInteger('marking_period_id'); // Links to marking period (e.g., semester, quarter)
            $table->unsignedBigInteger('subject_id'); // Links to course_subjects for subject-specific exams
            $table->enum('type', ['midterm', 'final', 'quiz'])->default('midterm');
            $table->string('description')->nullable(); // Optional exam details
            $table->string('exam_type', 20)->nullable(); // e.g., 'Midterm', 'Final', 'Quiz'
            $table->decimal('weight', 5, 2)->default(1.00); // Weight in grade calculation (e.g., 1.00 = 100%)
            $table->date('exam_date'); // Date of the exam
            $table->time('start_time')->nullable(); // Start time of the exam
            $table->time('end_time')->nullable(); // End time of the exam
            $table->integer('duration_minutes')->nullable(); // Duration in minutes
            $table->string('status', 20)->default('scheduled'); // e.g., 'scheduled', 'completed', 'cancelled'
            $table->unsignedBigInteger('created_by'); // Staff member who created the exam

            // Additional Metadata
            $table->boolean('is_published')->default(false); // Whether exam details are visible to students/parents
            $table->decimal('max_score', 5, 2)->nullable(); // Maximum possible score (e.g., 100.00)
            $table->text('instructions')->nullable(); // Exam instructions

            // Timestamps
            $table->timestamps(); // created_at and updated_at

            // Foreign Keys
            $table->foreign(['school_id', 'syear'])
                ->references(['id', 'syear'])
                ->on('schools')
                ->onUpdate('no action')
                ->onDelete('cascade');

            $table->foreign('marking_period_id')
                ->references('marking_period_id')
                ->on('school_marking_periods')
                ->onUpdate('no action')
                ->onDelete('cascade');

            $table->foreign('subject_id')
                ->references('subject_id')
                ->on('subjects')
                ->onUpdate('no action')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('staff_id')
                ->on('staff')
                ->onUpdate('no action')
                ->onDelete('no action');

            // Indexes
            $table->index(['school_id', 'syear'], 'school_id');
            $table->index('marking_period_id', 'exams_marking_period_id');
            $table->index('subject_id', 'exams_subject_id');
            $table->index('exam_date', 'exams_exam_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('exams');
    }
}
