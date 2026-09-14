<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolMarkingPeriodsTable extends Migration
{
    public function up()
    {
        Schema::create('school_marking_periods', function (Blueprint $table) {
            // Columns
            $table->id('marking_period_id'); // INT NOT NULL AUTO_INCREMENT, PRIMARY KEY
            $table->decimal('syear', 4, 0); // DECIMAL(4,0) NOT NULL
            $table->string('mp', 3); // VARCHAR(3) NOT NULL
            $table->unsignedBigInteger('school_id'); // INT NOT NULL (part of foreign key)
            $table->string('title', 50); // VARCHAR(50) NOT NULL
            $table->string('short_name', 10)->nullable();
            $table->decimal('sort_order', 10, 0)->nullable();
            $table->date('start_date'); // DATE NOT NULL
            $table->date('end_date'); // DATE NOT NULL
            $table->date('post_start_date')->nullable();
            $table->date('post_end_date')->nullable();
            $table->string('does_grades', 1)->nullable();
            $table->string('does_comments', 1)->nullable();
            $table->unsignedBigInteger('rollover_id')->nullable();
            $table->timestamps(); // created_at and updated_at with modern defaults

            // Indexes
            $table->index(['school_id', 'syear'], 'school_id');
            $table->index(['syear', 'school_id', 'start_date', 'end_date'], 'school_marking_periods_ind2');

            // Foreign Key
            $table->foreign(['school_id', 'syear'])
                ->references(['id', 'syear'])
                ->on('schools')
                ->onUpdate('no action')
                ->onDelete('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('school_marking_periods');
    }
}
