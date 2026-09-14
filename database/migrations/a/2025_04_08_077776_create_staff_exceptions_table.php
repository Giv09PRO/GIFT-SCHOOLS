<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStaffExceptionsTable extends Migration
{
    public function up()
    {
        Schema::create('staff_exceptions', function (Blueprint $table) {
            // Columns
            $table->unsignedBigInteger('user_id'); // INT NOT NULL (part of composite PK)
            $table->string('modname', 150); // VARCHAR(150) NOT NULL (part of composite PK)
            $table->string('can_use', 1)->nullable();
            $table->string('can_edit', 1)->nullable();
            $table->timestamps(); // created_at and updated_at with modern defaults

            // Primary Key (composite: user_id, modname)
            $table->primary(['user_id', 'modname']);

            // Foreign Key
            $table->foreign('user_id')
                ->references('staff_id')
                ->on('staff')
                ->onUpdate('no action')
                ->onDelete('no action');
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_exceptions');
    }
}
