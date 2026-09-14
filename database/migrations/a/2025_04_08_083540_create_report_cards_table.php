<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportCardsTable extends Migration
{
    public function up()
    {
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->decimal('syear', 4, 0); // School year
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('marking_period_id'); // e.g., Semester 1
            $table->unsignedBigInteger('grade_id')->nullable(); // From student_enrollment or school_gradelevels
            $table->json('subject_grades')->nullable(); // e.g., {"Math": {"average": 85.5, "letter": "B"}}
            $table->text('comments')->nullable(); // Overall comments
            $table->boolean('is_published')->default(false); // Visible to student/parent
            $table->unsignedBigInteger('generated_by')->nullable(); // Staff who generated it
            $table->timestamps();

            // Foreign Keys
            $table->foreign(['school_id', 'syear'])
                ->references(['id', 'syear'])
                ->on('schools')
                ->onDelete('cascade');
            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');
            $table->foreign('marking_period_id')
                ->references('marking_period_id')
                ->on('school_marking_periods')
                ->onDelete('cascade');
            $table->foreign('grade_id')
                ->references('id')
                ->on('school_gradelevels')
                ->onDelete('set null');
            $table->foreign('generated_by')
                ->references('staff_id')
                ->on('staff')
                ->onDelete('set null');

            // Indexes
            $table->index(['student_id', 'marking_period_id'], 'report_cards_student_mp');
            $table->index(['school_id', 'syear'], 'report_cards_school');

            $table->engine = 'InnoDB';
        });
    }

    public function down()
    {
        Schema::dropIfExists('report_cards');
    }
}
