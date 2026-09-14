<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
// If you need to use HasFactory or other traits, uncomment them
// use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeePayment extends Pivot
{
    // use HasFactory; // Uncomment if you plan to use factories for this pivot model

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fee_payment';

    /**
     * Indicates if the IDs are auto-incrementing.
     * Set to true if your 'fee_payment' table has an 'id' primary key that auto-increments.
     *
     * @var bool
     */
    public $incrementing = true; // Assuming your pivot table has an 'id' PK

    /**
     * The attributes that are mass assignable.
     * It's good practice to define these, though the command uses `create()` with explicit attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'fee_id',
        'payment_id',
        'amount_applied',
        'receipt_no',
        'receipt_book',
        // Add 'created_at' and 'updated_at' if you want Laravel to manage them
        // and your table has these columns. If using withTimestamps() on the
        // BelongsToMany relationship, Laravel handles them automatically for the pivot.
    ];

    /**
     * Indicates if the model should be timestamped.
     * Set to true if your 'fee_payment' table has `created_at` and `updated_at` columns
     * and you want Laravel to manage them.
     *
     * @var bool
     */
    public $timestamps = true; // Set to false if your pivot table doesn't have these,
    // or if you handle them manually, or if withTimestamps() is used
    // on the BelongsToMany relationship definition in Fee/Payment models.

    // Define relationships if needed (e.g., to Fee or Payment model from the pivot perspective)
     public function fee()
     {
         return $this->belongsTo(Fee::class, 'fee_id');
     }

     public function payment()
     {
         return $this->belongsTo(Payment::class, 'payment_id');
     }
}
