<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubjectsTable extends Migration
{
    public function up()
    {
        Schema::create('subjects', function (Blueprint $table) {
            // Columns
            $table->decimal('syear', 4, 0); // DECIMAL(4,0) NOT NULL
            $table->unsignedBigInteger('school_id'); // INT NOT NULL (part of foreign key)
            $table->id('subject_id'); // INT NOT NULL AUTO_INCREMENT, PRIMARY KEY
            $table->string('title', 100);
            $table->string('short_name', 25)->nullable()->default(null);
            $table->decimal('sort_order', 10, 0)->nullable()->default(null);
            $table->unsignedBigInteger('rollover_id')->nullable()->default(null);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            // Indexes
            $table->index(['school_id', 'syear'], 'school_id');

            // Foreign Key Constraint
            $table->foreign(['school_id', 'syear'])
                ->references(['id', 'syear'])
                ->on('schools')
                ->onUpdate('no action')
                ->onDelete('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('subjects');
    }
}
