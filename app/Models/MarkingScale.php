<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon; // Import Carbon

/**
 * Represents a marking scale entry used for grading within a specific school and year.
 *
 * Corresponds to the 'marking_scales' table.
 *
 * @method static Builder|MarkingScale newModelQuery()
 * @method static Builder|MarkingScale newQuery()
 * @method static Builder|MarkingScale query()
 * @method static Builder|MarkingScale where(string $column, mixed $value)
 * @method static Builder|MarkingScale whereId($value)
 * @method static Builder|MarkingScale whereSchoolId($value)
 * @method static Builder|MarkingScale whereSyear($value)
 *
 * @property int $id The unique identifier for the marking scale entry.
 * @property int $syear The school year this scale applies to.
 * @property int $school_id Foreign key for the associated school.
 * @property string $name The name of this specific scale entry (e.g., "Excellent", "Good").
 * @property float $min_score The minimum score for this grade/mark.
 * @property float $max_score The maximum score for this grade/mark.
 * @property string $letter_grade The letter grade associated with this score range (e.g., "A", "B").
 * @property string|null $comment An optional comment associated with this grade/mark.
 * @property float $weight The weight of this scale entry if used in calculations.
 * @property int|null $created_by Foreign key for the staff member who created this entry.
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read School $school The associated School model.
 * @property-read Staff|null $creator The associated Staff model (creator). Alias for createdBy.
 */
class MarkingScale extends Model
{
    use HasFactory; // Added HasFactory trait

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'marking_scales'; // Explicitly define the table name

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int'; // Or 'bigint' if strictly needed

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     * Corresponds to created_at and updated_at columns.
     *
     * @var bool
     */
    public $timestamps = true; // Explicitly set

    /**
     * The attributes that are mass assignable.
     * Added 'comment' based on schema.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'syear',
        'school_id',
        'name',
        'min_score',
        'max_score',
        'letter_grade',
        'comment', // Added comment field
        'weight',
        'created_by'
    ];

    /**
     * The attributes that should be cast.
     * Added casts for numeric types and timestamps.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'syear' => 'integer',
        'school_id' => 'integer',
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'weight' => 'decimal:2',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the school that this marking scale belongs to.
     * IMPORTANT: The 'schools' table has a composite primary key (id, syear).
     * Standard Eloquent belongsTo works best with single-column keys on the parent.
     * This relationship links only by 'school_id'. You MUST ensure the 'syear'
     * context is handled correctly in your queries when using this relation.
     * Example: MarkingScale::where('syear', $currentYear)->with('school')->get();
     *
     * The syntax `belongsTo(School::class, ['school_id', 'syear'], ['id', 'syear'])` is incorrect.
     */
    public function school(): BelongsTo
    {
        // Corrected relationship definition (links only by school_id)
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    /**
     * Get the staff member who created this marking scale entry.
     */
    public function createdBy(): BelongsTo
    {
        // Assumes Staff model exists and its primary key is 'staff_id'.
        // Adjust 'staff_id' if the primary key on the Staff model is different (e.g., 'id').
        return $this->belongsTo(Staff::class, 'created_by', 'staff_id');
    }

    /**
     * Alias for createdBy relationship for potentially clearer semantics.
     */
    public function creator(): BelongsTo
    {
        return $this->createdBy();
    }
}
