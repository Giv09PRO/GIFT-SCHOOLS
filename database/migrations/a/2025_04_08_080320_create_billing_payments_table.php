<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('billing_payments', function (Blueprint $table) {
            // Columns
            $table->id(); // INT NOT NULL AUTO_INCREMENT, PRIMARY KEY
            $table->decimal('syear', 4, 0); // DECIMAL(4,0) NOT NULL
            $table->unsignedBigInteger('school_id'); // INT NOT NULL (part of foreign key)
            $table->unsignedBigInteger('student_id'); // INT NOT NULL (foreign key)
            $table->decimal('amount', 14, 2); // DECIMAL(14,2) NOT NULL
            $table->date('payment_date')->nullable()->default(null);
            $table->text('comments')->nullable();
            $table->unsignedBigInteger('refunded_payment_id')->nullable()->default(null);
            $table->string('lunch_payment', 1)->nullable()->default(null);
            $table->text('file_attached')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->text('created_by')->nullable();

            // Indexes
            $table->index('student_id');
            $table->index(['school_id', 'syear'], 'school_id');
            $table->index('amount', 'billing_payments_ind2');
            $table->index('refunded_payment_id', 'billing_payments_ind3');

            // Foreign Key Constraints
            $table->foreign('student_id')
                ->references('id') // Assuming 'id' is the PK of students; adjust if it's 'student_id'
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
        Schema::dropIfExists('billing_payments');
    }
}
