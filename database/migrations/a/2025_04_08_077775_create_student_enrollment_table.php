<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentEnrollmentTable extends Migration
{
    public function up()
    {
        Schema::create('student_enrollment', function (Blueprint $table) {
            // Columns
            $table->id(); // INT NOT NULL AUTO_INCREMENT, PRIMARY KEY
            $table->decimal('syear', 4, 0)->default(2024); // DECIMAL(4,0) NOT NULL DEFAULT '2024'
            $table->unsignedBigInteger('school_id')->default(1); // INT NOT NULL DEFAULT '1'
            $table->unsignedBigInteger('student_id'); // INT NOT NULL
            $table->unsignedBigInteger('grade_id')->nullable()->default(4); // INT NULL DEFAULT '4'
            $table->date('start_date')->nullable()->default('2024-06-07'); // DATE NULL DEFAULT '2024-06-07'
            $table->date('end_date')->nullable(); // DATE NULL DEFAULT NULL
            $table->integer('enrollment_code')->nullable()->default(3); // INT NULL DEFAULT '3'
            $table->integer('drop_code')->nullable(); // INT NULL DEFAULT NULL
            $table->unsignedBigInteger('next_school')->nullable()->default(1); // INT NULL DEFAULT '1'
            $table->unsignedBigInteger('calendar_id')->nullable()->default(1); // INT NULL DEFAULT '1'
            $table->unsignedBigInteger('last_school')->nullable()->default(1); // INT NULL DEFAULT '1'
            $table->timestamps(); // created_at and updated_at with modern defaults

            // Indexes
            $table->index('student_id', 'student_id');
            $table->index(['school_id', 'syear'], 'school_id');
            $table->index('grade_id', 'student_enrollment_2');
            $table->index(['start_date', 'end_date'], 'student_enrollment_4');

            // Foreign Keys
            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreign(['school_id', 'syear'])
                ->references(['id', 'syear'])
                ->on('schools')
                ->onUpdate('no action')
                ->onDelete('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_enrollment');
    }
}
