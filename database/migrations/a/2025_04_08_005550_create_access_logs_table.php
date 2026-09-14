<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccessLogsTable extends Migration
{
    public function up()
    {
        Schema::create('access_log', function (Blueprint $table) {
            // Columns
            $table->id(); // Added for Laravel convention; adjust if not needed
            $table->decimal('syear', 4, 0); // DECIMAL(4,0) NOT NULL
            $table->string('username', 100)->nullable();
            $table->string('profile', 30)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamps(); // created_at and updated_at with modern defaults
        });
    }

    public function down()
    {
        Schema::dropIfExists('access_log');
    }
}
