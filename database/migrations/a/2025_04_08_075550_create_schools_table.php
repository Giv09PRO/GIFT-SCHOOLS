<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolsTable extends Migration
{
    public function up()
    {
        Schema::create('schools', function (Blueprint $table) {
            // Columns
            $table->decimal('syear', 4, 0); // DECIMAL(4,0) NOT NULL
            $table->unsignedBigInteger('id', true); // INT NOT NULL AUTO_INCREMENT
            $table->string('title', 100);
            $table->string('address', 100)->nullable()->default(null);
            $table->string('city', 100)->nullable()->default(null);
            $table->string('state', 15)->nullable()->default(null);
            $table->string('zipcode', 10)->nullable()->default(null);
            $table->string('phone', 30)->nullable()->default(null);
            $table->string('principal', 100)->nullable()->default(null);
            $table->text('www_address')->nullable()->default(null);
            $table->string('school_number', 50)->nullable()->default(null);
            $table->string('short_name', 25)->nullable()->default(null);
            $table->decimal('reporting_gp_scale', 10, 3)->nullable()->default(null);
            $table->decimal('number_days_rotation', 1, 0)->nullable()->default(null);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            // Primary Key (composite: id, syear)
            $table->primary(['id', 'syear']);

            // Indexes
            $table->index('syear', 'schools_ind1');

        });
    }

    public function down()
    {
        Schema::dropIfExists('schools');
    }
}
