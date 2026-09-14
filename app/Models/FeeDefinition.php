<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon; // Import Carbon for date handling if needed, though not directly used in casts here

/**
 * Represents a master fee definition in the system.
 *
 * Corresponds to the 'fee_definitions' table.
 * This table stores the overall structure of a fee, such as its total amount
 * and how many installments it's typically broken into.
 *
 * @property int $id The unique identifier for the fee definition.
 * @property int $school_id Foreign key for the associated school.
 * @property int $syear The school year this fee definition applies to.
 * @property string $fee_name The descriptive name of the fee (e.g., "Annual School Fees", "Exam Fee").
 * @property float $total_amount The total amount for this fee type before any division into installments.
 * @property int $number_of_installments The number of installments this fee is typically divided into (e.g., 1, 4).
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read School $school The associated School model.
 * @property-read \Illuminate\Database\Eloquent\Collection|Fee[] $installments The collection of individual fee installments (from billing_fees) associated with this definition.
 */
class FeeDefinition extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fee_definitions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'syear',
        'fee_name',
        'total_amount',
        'number_of_installments',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'school_id' => 'integer',
        'syear' => 'integer',
        'total_amount' => 'decimal:2',
        'number_of_installments' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the school that this fee definition belongs to.
     *
     * This relationship assumes that the `schools` table has 'id' and 'syear'
     * as a composite primary key or that `school_id` alone is sufficient
     * if `syear` on `fee_definitions` is just for scoping/filtering.
     * If `schools` uses a simple 'id' primary key, this is fine.
     * If `schools` requires both `id` and `syear` for the join,
     * this relationship might need adjustment or a more complex scope.
     * Given the foreign key `fk_fee_definitions_school_syear` references `schools(id, syear)`,
     * a simple belongsTo on `school_id` might not fully represent the constraint
     * without additional scoping in queries. However, for basic retrieval by `school_id`, it works.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function scopeForSchoolAndYear(Builder $query, int $schoolId, int $syear)
    {
        return $query->where('school_id', $schoolId)->where('syear', $syear);
    }

    /**
     * Get all the individual fee installments (from billing_fees table)
     * that belong to this fee definition.
     */
    public function installments(): HasMany
    {
        // A FeeDefinition has many Fee (installments)
        // The foreign key on the 'billing_fees' table is 'fee_definition_id'
        return $this->hasMany(Fee::class, 'fee_definition_id');
    }

    // You can add accessors or other model-specific logic here if needed.
    // For example, an accessor to get the per-installment amount if it's uniform:
     public function getPerInstallmentAmountAttribute(): ?float
     {
         if ($this->number_of_installments > 0 && $this->total_amount > 0) {
             return round($this->total_amount / $this->number_of_installments, 2);
         }
         return null;
     }
}
