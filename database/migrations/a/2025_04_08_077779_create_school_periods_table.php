<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolPeriodsTable extends Migration
{
    public function up()
    {
        Schema::create('school_periods', function (Blueprint $table) {
            // Columns
            $table->id('period_id'); // INT NOT NULL AUTO_INCREMENT, PRIMARY KEY
            $table->decimal('syear', 4, 0); // DECIMAL(4,0) NOT NULL
            $table->unsignedBigInteger('school_id'); // INT NOT NULL (part of foreign key)
            $table->decimal('sort_order', 10, 0)->nullable();
            $table->string('title', 100); // VARCHAR(100) NOT NULL
            $table->string('short_name', 10)->nullable();
            $table->integer('length')->nullable();
            $table->string('start_time', 10)->nullable();
            $table->string('end_time', 10)->nullable();
            $table->string('block', 10)->nullable();
            $table->string('attendance', 1)->nullable();
            $table->unsignedBigInteger('rollover_id')->nullable();
            $table->timestamps(); // created_at and updated_at with modern defaults

            // Indexes
            $table->index(['school_id', 'syear'], 'school_id');

            // Foreign Key
            $table->foreign(['school_id', 'syear'])
                ->references(['id', 'syear'])
                ->on('schools')
                ->onUpdate('no action')
                ->onDelete('no action');

            // Engine
            $table->engine = 'InnoDB';
        });
    }

    public function down()
    {
        Schema::dropIfExists('school_periods');
    }
}
