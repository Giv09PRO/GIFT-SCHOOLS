<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            // Columns
            $table->id(); // INT NOT NULL AUTO_INCREMENT, PRIMARY KEY
            $table->string('profile', 30)->nullable(); // VARCHAR(30) NULL DEFAULT NULL
            $table->text('title'); // TEXT NOT NULL
            $table->timestamps(); // created_at and updated_at with modern defaults
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_profiles');
    }
}
