<?php /** @noinspection GrazieInspection */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

// Import Collection

/**
 * Represents a payment made by a student.
 *
 * Corresponds to the 'billing_payments' table.
 *
 * @method static Builder|Payment newModelQuery()
 * @method static Builder|Payment newQuery()
 * @method static Builder|Payment query()
 * @method static Builder|Payment where(string $column, mixed $value)
 * @method static Builder|Payment whereId($value)
 * @method static Builder|Payment whereStudentId(int $studentId)
 * @method static Builder|Payment whereSchoolId(int $schoolId)
 * @method static Builder|Payment whereSyear(int $syear)
 *
 * @property int $id The unique identifier for the payment.
 * @property int $syear The school year this payment belongs to.
 * @property int $school_id Foreign key for the associated school.
 * @property int $student_id Foreign key for the associated student.
 * @property float $amount The amount of the payment.
 * @property Carbon|null $payment_date The date the payment was made.
 * @property string|null $comments Optional comments about the payment.
 * @property int|null $refunded_payment_id If this is a refund, the ID of the original payment being refunded.
 * @property bool $lunch_payment Flag indicating if this is specifically a lunch payment (based on schema 'varchar(1)', cast to boolean).
 * @property string|null $file_attached Path to an attached file, if any.
 * @property int|null $created_by ID of the staff member who recorded the payment (schema type is a text, but likely stores an ID).
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read bool $is_refund Accessor to check if this payment is a refund.
 * @property-read float $amount_applied Accessor to calculate the total amount applied to fees.
 *
 * @property-read Student $student The associated Student model.
 * @property-read School $school The associated School model.
 * @property-read Payment|null $refundedPayment The original payment this one refunds.
 * @property-read Collection|Payment[] $refunds Any payments that refund this payment.
 * @property-read Collection|Fee[] $fees The fees this payment has been applied to.
 * @property-read Staff|null $creator The associated Staff model (creator).
 */
class Payment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'billing_payments';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int'; // Or 'bigint'

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true; // Corresponds to created_at and updated_at

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'syear',
        'school_id',
        'student_id',
        'amount',
        'payment_date',
        'comments',
        'refunded_payment_id', // ID of the payment being refunded
        'lunch_payment',       // Flag for lunch payment
        'file_attached',
        'created_by'           // ID of staff who created it
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'syear' => 'integer', // Changed from decimal: 0 to integer
        'school_id' => 'integer',
        'student_id' => 'integer',
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'refunded_payment_id' => 'integer',
        // Cast varchar(1) 'Y'/'N' or '1'/'0' to boolean
        'lunch_payment' => 'boolean',
        'created_by' => 'integer', // Assuming created_by stores an integer ID despite schema 'text'
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the student that owns the payment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the school that owns the payment.
     * IMPORTANT: See note in MarkingScale/GradeLevel models regarding composite keys.
     * This links only by 'school_id'. Ensure 'syear' context is handled.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    /**
     * Get the original payment that this payment refunded (if applicable).
     * This defines a self-referencing relationship for refunds.
     */
    public function refundedPayment(): BelongsTo
    {
        // Links 'refunded_payment_id' on this model to 'id' on another Payment model.
        return $this->belongsTo(Payment::class, 'refunded_payment_id', 'id');
    }

    /**
     * Get the refund payments associated with this original payment.
     * This is the inverse of the refundedPayment relationship.
     */
    public function refunds(): HasMany
    {
        // Finds other Payment models where 'refunded_payment_id' matches this model's 'id'.
        return $this->hasMany(Payment::class, 'refunded_payment_id', 'id');
    }

    /**
     * Define the many-to-many relationship with Fees.
     * A payment can be applied to multiple fees.
     */
    public function fees(): BelongsToMany
    {
        return $this->belongsToMany(Fee::class, 'fee_payment', 'payment_id', 'fee_id')
            ->withPivot('amount_applied', 'receipt_no', 'receipt_book') // Include pivot data
            ->withTimestamps(); // If fee_payment table has timestamps
    }

    /**
     * Get the staff member who created the payment.
     * Assumes 'created_by' column stores the staff ID.
     * The schema shows 'created_by' as TEXT, which is unusual for a foreign key.
     * Casting to integer and relating to Staff assumes it reliably stores the staff_id.
     */
    public function creator(): BelongsTo
    {
        // Ensure Staff model exists and its primary key is 'staff_id'. Adjust if needed.
        return $this->belongsTo(Staff::class, 'created_by', 'staff_id');
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Determine if this payment is a refund.
     * Accessor: $payment->is_refund
     *
     * @return bool
     */
    public function getIsRefundAttribute(): bool
    {
        return $this->refunded_payment_id !== null;
    }

    /**
     * Calculate the total amount of this payment that has been applied to fees.
     * Accessor: $payment->amount_applied
     * Requires the 'fees' relationship to be loaded with pivot data.
     *
     * @return float
     */
    public function getAmountAppliedAttribute(): float
    {
        if (! $this->relationLoaded('fees')) {
            // Avoid N+1. Return 0 or consider loading if absolutely necessary, but warn.
             Log::warning('Accessed Payment->amount_applied without eager loading fees relationship.');
            return 0.0;
        }
        if (is_null($this->fees)) {
            return 0.0;
        }
        // Sum the 'amount_applied' from the pivot table 'fee_payment'.
        return (float) $this->fees->sum('pivot.amount_applied');
    }
}
