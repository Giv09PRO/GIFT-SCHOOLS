<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffTable extends Migration
{
    public function up()
    {
        Schema::create('staff', function (Blueprint $table) {
            // Columns
            $table->decimal('syear', 4, 0); // DECIMAL(4,0) NOT NULL
            $table->id('staff_id'); // INT NOT NULL AUTO_INCREMENT, PRIMARY KEY
            $table->unsignedBigInteger('current_school_id')->nullable();
            $table->string('title', 5)->nullable();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('middle_name', 50)->nullable();
            $table->string('name_suffix', 3)->nullable();
            $table->string('username', 100)->nullable();
            $table->string('password', 106)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('custom_200000001')->nullable();
            $table->decimal('current_school_syear', 4, 0)->nullable();
            $table->string('profile', 30)->nullable();
            $table->string('schools', 150)->nullable();
            $table->dateTime('last_login')->nullable();
            $table->integer('failed_login')->nullable();
            $table->unsignedBigInteger('profile_id')->nullable();
            $table->unsignedBigInteger('rollover_id')->nullable();
            $table->timestamps(); // created_at and updated_at with modern defaults

            // Indexes
            $table->unique(['username', 'syear'], 'staff_ind4');
            $table->index(['staff_id', 'syear'], 'staff_ind1');
            $table->index(['last_name', 'first_name'], 'staff_ind2');
            $table->index('schools', 'staff_ind3');

            // Foreign Key (optional, adjust as needed)
            $table->foreign(['current_school_id', 'current_school_syear'])
                ->references(['id', 'syear'])
                ->on('schools')
                ->onUpdate('no action')
                ->onDelete('set null');

        });
    }

    public function down()
    {
        Schema::dropIfExists('staff');
    }
}
