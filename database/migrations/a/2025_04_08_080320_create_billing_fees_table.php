<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingFeesTable extends Migration
{
    public function up()
    {
        Schema::create('billing_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->default(1); // INT NOT NULL DEFAULT '1'
            $table->unsignedBigInteger('student_id'); // INT NOT NULL
            $table->date('assigned_date')->nullable()->default(null);
            $table->date('due_date')->nullable()->default(null);
            $table->text('comments')->nullable();
            $table->text('title');
            $table->decimal('amount', 14, 2);
            $table->text('file_attached')->nullable();
            $table->decimal('syear', 4, 0);
            $table->foreignId('waived_fee_id')->nullable()->default(null); // Adjust if this references another table
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->text('created_by')->nullable()->collation('utf8mb4_unicode_520_ci');

// Indexes
            $table->index('student_id');
            $table->index(['school_id', 'syear']);

            // foreign keys
            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onUpdate('no action')
                ->onDelete('no action');

            $table->foreign(['school_id', 'syear'])
                ->references(['id', 'syear'])
                ->on('schools')
                ->onUpdate('no action')
                ->onDelete('no action');

        });
    }

    public function down()
    {
        Schema::dropIfExists('billing_fees');
    }
}
