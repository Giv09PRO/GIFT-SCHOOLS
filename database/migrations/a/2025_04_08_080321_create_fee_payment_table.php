<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the fee_payment pivot table to link fees and payments,
     * storing the amount of a payment applied to a specific fee.
     */
    public function up(): void
    {
        Schema::create('fee_payment', function (Blueprint $table) {
            $table->id(); // Primary key for the pivot record itself

            // Foreign key linking to the billing_fees table
            $table->foreignId('fee_id')
                ->constrained('billing_fees') // Assumes your fees table is named 'billing_fees'
                ->onDelete('cascade'); // If a fee is deleted, remove related pivot entries

            // Foreign key linking to the billing_payments table
            $table->foreignId('payment_id')
                ->constrained('billing_payments') // Assumes your payments table is named 'billing_payments'
                ->onDelete('cascade'); // If a payment is deleted, remove related pivot entries

            // The amount of this specific payment applied to this specific fee
            $table->decimal('amount_applied', 10); // Adjust precision/scale as needed (e.g., 10 total digits, 2 after decimal)

            $table->timestamps(); // Optional: created_at and updated_at for the pivot record

            // Add unique constraint to prevent duplicate entries for the same fee/payment pair (optional but recommended)
            $table->unique(['fee_id', 'payment_id']);

            // Add indexes for performance
            $table->index('fee_id');
            $table->index('payment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the fee_payment table.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_payment');
    }
};
