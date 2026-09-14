<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Import BelongsToMany
use Illuminate\Support\Collection; // Import Collection
use Carbon\Carbon; // Import Carbon for date comparison

/**
 * Represents a billing fee installment assigned to a student.
 *
 * Corresponds to the 'billing_fees' table. Each record is an installment of a defined fee.
 * Includes accessors for calculated properties like total paid, balance, waived status, paid status, and overdue status.
 *
 * @method static Builder|Fee where(string $column, mixed $value) Scope queries.
 * @method static Builder|Fee whereStudentId(int $studentId) Scope by student ID.
 * @method static Builder|Fee whereSchoolId(int $schoolId) Scope by school ID.
 * @method static Builder|Fee whereSyear(int $syear) Scope by school year.
 * @method static Builder|Fee whereFeeDefinitionId(int $feeDefinitionId) Scope by fee definition ID.
 *
 * @property int $id The unique identifier for the fee installment.
 * @property int $school_id Foreign key for the associated school.
 * @property int $student_id Foreign key for the associated student.
 * @property int|null $fee_definition_id Foreign key linking to the fee_definitions table.
 * @property int|null $installment_number The number of this installment (e.g., 1, 2, 3).
 * @property string|null $invoice_no The invoice number associated with this fee installment.
 * @property Carbon|null $assigned_date The date the fee installment was assigned.
 * @property Carbon|null $due_date The date the fee installment is due.
 * @property string|null $comments Optional comments about the fee installment.
 * @property string $title The title or description of the fee installment (e.g., "School Fees - Term 1").
 * @property float $amount The original amount of this specific fee installment.
 * @property float $waived_amount The total amount that has been waived for this fee installment.
 * @property string|null $file_attached Path to an attached file, if any.
 * @property int $syear The school year this fee installment belongs to.
 * @property int|null $waived_fee_id If not null, indicates the fee installment is waived (legacy or specific full waiver flag).
 * @property int|null $created_by ID of the staff member who created the fee installment.
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read float $total_paid Accessor for calculated total paid towards this fee installment.
 * @property-read float $balance Accessor for calculated remaining balance for this installment (amount - total_paid - waived_amount).
 * @property-read bool $is_waived Accessor to check if any amount has been waived for this fee installment.
 * @property-read bool $is_fully_waived Accessor to check if the fee installment is fully covered by waivers.
 * @property-read bool $is_paid Accessor to check if the fee installment is fully paid (balance is zero or negligible).
 * @property-read bool $is_overdue Accessor to check if the fee installment is past its due date and not paid/fully waived.
 * @property-read string $status Accessor for a textual representation of the fee status (e.g., "Paid", "Partially Waived", "Overdue").
 *
 * @property-read Student $student The associated Student model.
 * @property-read School $school The associated School model.
 * @property-read Staff|null $creator The associated Staff model (creator).
 * @property-read FeeDefinition|null $feeDefinition The associated FeeDefinition model.
 * @property-read Collection|Payment[] $payments The collection of associated Payment models through the pivot table.
 */
class Fee extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'billing_fees';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'student_id',
        'fee_definition_id',
        'installment_number',
        'invoice_no',
        'assigned_date',
        'due_date',
        'comments',
        'title',
        'amount',
        'waived_amount', // Added waived_amount
        'file_attached',
        'syear',
        'waived_fee_id', // Kept for now, might be used as a flag for "explicit full waiver action"
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'assigned_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'waived_amount' => 'decimal:2', // Added cast for waived_amount
        'syear' => 'integer',
        'school_id' => 'integer',
        'student_id' => 'integer',
        'fee_definition_id' => 'integer',
        'installment_number' => 'integer',
        'created_by' => 'integer',
        'waived_fee_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the student that owns the fee installment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * Get the school that owns the fee installment.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * Get the fee definition that this installment belongs to.
     */
    public function feeDefinition(): BelongsTo
    {
        return $this->belongsTo(FeeDefinition::class, 'fee_definition_id');
    }

    /**
     * Define the many-to-many relationship with Payments.
     */
    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'fee_payment', 'fee_id', 'payment_id')
            ->withPivot('amount_applied')
            ->withTimestamps();
    }

    /**
     * Get the staff member who created the fee installment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by', 'staff_id');
    }


    // =========================================================================
    // Accessors
    // =========================================================================

    /**
     * Calculate the total amount paid towards this fee installment.
     * Accessor: $fee->total_paid
     */
    public function getTotalPaidAttribute(): float
    {
        // Ensure payments relation is loaded to avoid N+1 issues.
        // If not loaded, this will execute a query for each Fee model.
        if (! $this->relationLoaded('payments')) {
            $this->load('payments'); // Lazy load if not already loaded. Consider eager loading in queries.
        }
        return (float) $this->payments->sum('pivot.amount_applied');
    }

    /**
     * Calculate the remaining balance for this fee installment.
     * Balance = Original Amount - Total Paid - Total Waived Amount
     * Accessor: $fee->balance
     */
    public function getBalanceAttribute(): float
    {
        $balance = $this->amount - $this->total_paid - ($this->waived_amount ?? 0.0);
        return round($balance, 2); // Using round to handle potential float precision issues
    }

    /**
     * Determine if any amount has been waived for this fee installment.
     * Accessor: $fee->is_waived
     */
    public function getIsWaivedAttribute(): bool
    {
        return ($this->waived_amount ?? 0.0) > 0.005; // Check if any significant amount is waived
    }

    /**
     * Determine if the fee installment is fully covered by waivers.
     * This means the waived amount is equal to or greater than the original fee amount.
     * Accessor: $fee->is_fully_waived
     */
    public function getIsFullyWaivedAttribute(): bool
    {
        return ($this->waived_amount ?? 0.0) >= $this->amount - 0.005; // Check if waived amount covers the original fee
    }


    /**
     * Determine if the fee installment is fully paid (balance is zero or negligible).
     * This considers payments and waivers.
     * Accessor: $fee->is_paid
     */
    public function getIsPaidAttribute(): bool
    {
        // A fee is considered paid if its outstanding balance is zero or negligible.
        return $this->balance < 0.005;
    }

    /**
     * Determine if the fee installment is overdue.
     * An overdue fee has a balance greater than zero, is not fully waived to cover the balance,
     * and its due date is in the past.
     * Accessor: $fee->is_overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        // Not considered overdue if it's already paid off (balance is zero)
        if ($this->is_paid) {
            return false;
        }

        // If there's a due date and it's in the past, then it's overdue.
        return $this->due_date instanceof Carbon && $this->due_date->isPast();
    }

    /**
     * Get a textual representation of the fee status.
     * Accessor: $fee->status
     */
    public function getStatusAttribute(): string
    {
        $amount = $this->amount ?? 0.0;
        $paid = $this->total_paid ?? 0.0;
        $waived = $this->waived_amount ?? 0.0;

        // 1. Check for overpaid: paid exceeds amount (ignoring waivers)
        if ($paid > $amount + 0.005) {
            return 'Overpaid';
        }

        // 2. Fully Paid scenarios
        if ($this->is_paid) {
            // Paid, but partially waived
            if ($waived > 0.005 && $paid < $amount - 0.005) {
                return 'Paid (Waived)';
            }

            // Fully waived and no real payment
            if ($waived >= $amount - 0.005 && $paid < 0.005) {
                return 'Fully Waived';
            }

            return 'Paid';
        }

        // 3. Not paid, but partially waived
        if ($this->is_waived) {
            return 'Partially Waived';
        }

        // 4. Not paid, not waived, but partially paid
        if ($paid > 0.005) {
            return 'Partially Paid';
        }

        // 5. Overdue?
        if ($this->is_overdue) {
            return 'Overdue';
        }

        return 'Unpaid';
    }

}
