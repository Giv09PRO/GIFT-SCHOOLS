<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateResultsTable extends Migration
{
    public function up()
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('student_id');
            $table->decimal('score', 5, 2)->nullable(); // e.g., 85.50 out of max_score
            $table->text('comments')->nullable(); // Teacher feedback
            $table->unsignedBigInteger('graded_by')->nullable(); // Staff who graded it
            $table->boolean('is_finalized')->default(false); // Whether the score is locked
            $table->timestamps();

            // Foreign Keys
            $table->foreign('exam_id')
                ->references('id')
                ->on('exams')
                ->onDelete('cascade');

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');
            $table->foreign('graded_by')
                ->references('staff_id')
                ->on('staff')
                ->onDelete('set null');

            // Unique Constraint (one result per student per exam)
            $table->unique(['exam_id', 'student_id'], 'exam_student_unique');

            // Indexes
            $table->index('exam_id', 'exam_results_exam_id');
            $table->index('student_id', 'exam_results_student_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('exam_results');
    }
}
