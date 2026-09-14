<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            // Columns
            $table->id(); // INT NOT NULL AUTO_INCREMENT, PRIMARY KEY
            $table->string('last_name', 50);
            $table->string('first_name', 50);
            $table->string('middle_name', 50)->nullable();
            $table->string('name_suffix', 3)->nullable();
            $table->string('username', 100)->nullable()->unique('username');
            $table->string('password', 106)->nullable();
            $table->string('email', 20)->nullable();
            $table->dateTime('last_login')->nullable();
            $table->integer('failed_login')->nullable();
            $table->string('prem_number', 12)->nullable();
            $table->date('dob')->nullable();
            $table->text('custom_200000003')->nullable();
            $table->date('custom_200000004')->nullable();
            $table->text('custom_200000005')->nullable();
            $table->text('custom_200000006')->nullable();
            $table->text('custom_200000007')->nullable();
            $table->text('custom_200000008')->nullable();
            $table->longText('custom_200000009')->nullable();
            $table->char('custom_200000010', 1)->nullable();
            $table->longText('custom_200000011')->nullable();
            $table->timestamps(); // created_at and updated_at with modern defaults

            // Indexes
            $table->index(['last_name', 'first_name', 'middle_name'], 'name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('students');
    }
}
