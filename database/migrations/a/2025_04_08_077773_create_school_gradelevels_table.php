<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolGradelevelsTable extends Migration
{
    public function up()
    {
        Schema::create('school_gradelevels', function (Blueprint $table) {
            // Columns
            $table->id(); // INT NOT NULL AUTO_INCREMENT, PRIMARY KEY
            $table->unsignedBigInteger('school_id'); // INT NOT NULL
            $table->decimal('school_syear', 4, 0); // Add syear
            $table->string('short_name', 3)->nullable();
            $table->string('title', 50);
            $table->unsignedBigInteger('next_grade_id')->nullable();
            $table->decimal('sort_order', 10, 0)->nullable();
            $table->timestamps(); // created_at and updated_at with modern defaults

            // Indexes
            $table->index('school_id', 'school_gradelevels_ind1');
            $table->index('school_syear', 'school_gradelevels_ind2'); // Optional, for performance

            // Foreign Key (optional, adjust as needed)
            $table->foreign(['school_id', 'school_syear'])
                ->references(['id', 'syear'])
                ->on('schools')
                ->onUpdate('no action')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('school_gradelevels');
    }
}
