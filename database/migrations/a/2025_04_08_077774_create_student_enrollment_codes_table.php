<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentEnrollmentCodesTable extends Migration
{
    public function up()
    {
        Schema::create('student_enrollment_codes', function (Blueprint $table) {
            // Columns
            $table->id(); // INT NOT NULL AUTO_INCREMENT, PRIMARY KEY
            $table->decimal('syear', 4, 0); // DECIMAL(4,0) NOT NULL
            $table->string('title', 100); // VARCHAR(100) NOT NULL
            $table->string('short_name', 10)->nullable(); // VARCHAR(10) NULL DEFAULT NULL
            $table->string('type', 4)->nullable(); // VARCHAR(4) NULL DEFAULT NULL
            $table->string('default_code', 1)->nullable(); // VARCHAR(1) NULL DEFAULT NULL
            $table->decimal('sort_order', 10, 0)->nullable(); // DECIMAL(10,0) NULL DEFAULT NULL
            $table->timestamps(); // created_at and updated_at with modern defaults

            // Engine
            $table->engine = 'InnoDB';
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_enrollment_codes');
    }
}
