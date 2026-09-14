<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Represents a student's report card for a specific marking period.
 *
 * Corresponds to the 'report_cards' table.
 *
 * @method static Builder|ReportCard newModelQuery()
 * @method static Builder|ReportCard newQuery()
 * @method static Builder|ReportCard query()
 * @method static Builder|ReportCard where(string $column, mixed $value)
 * @method static Builder|ReportCard whereId($value)
 * @method static Builder|ReportCard whereStudentId(int $studentId)
 * @method static Builder|ReportCard whereSchoolId(int $schoolId)
 * @method static Builder|ReportCard whereSyear(int $syear)
 * @method static Builder|ReportCard whereMarkingPeriodId(int $mpId)
 * @method static Builder|ReportCard published() Scope to get only published report cards.
 *
 * @property int $id The unique identifier for the report card.
 * @property int $syear The school year this report card belongs to.
 * @property int $school_id Foreign key for the associated school.
 * @property int $student_id Foreign key for the associated student.
 * @property int $marking_period_id Foreign key for the associated marking period.
 * @property int|null $grade_id Foreign key for the associated grade level at the time of generation.
 * @property array|null $subject_grades JSON column storing grades for subjects (cast to array).
 * @property string|null $comments General comments on the report card.
 * @property bool $is_published Flag indicating if the report card is visible to students/parents.
 * @property int|null $generated_by Foreign key for the staff member who generated the report card.
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read School $school The associated School model.
 * @property-read Student $student The associated Student model.
 * @property-read SchoolMarkingPeriod $markingPeriod The associated SchoolMarkingPeriod model.
 * @property-read GradeLevel|null $gradeLevel The associated GradeLevel model.
 * @property-read Staff|null $generator The Staff model representing the generator.
 */
class ReportCard extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'report_cards';

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
     * Matches BIGINT UNSIGNED in the schema.
     *
     * @var string
     */
    protected $keyType = 'bigint';

    /**
     * Indicates if the model should be timestamped.
     * Laravel will automatically manage created_at and updated_at.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'syear',
        'school_id',
        'student_id',
        'marking_period_id',
        'grade_id',
        'subject_grades', // Ensure data being saved here is serializable (e.g., an array)
        'comments',
        'is_published',
        'generated_by',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'syear' => 'integer', // DECIMAL(4,0) -> integer
        'school_id' => 'integer', // BIGINT UNSIGNED -> integer
        'student_id' => 'integer', // BIGINT UNSIGNED -> integer
        'marking_period_id' => 'integer', // BIGINT UNSIGNED -> integer
        'grade_id' => 'integer', // BIGINT UNSIGNED -> integer
        'subject_grades' => 'array', // JSON -> array
        'is_published' => 'boolean', // TINYINT(1) -> boolean
        'generated_by' => 'integer', // BIGINT UNSIGNED -> integer
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the school that this report card belongs to.
     * IMPORTANT: The 'schools' table has a composite primary key (id, syear).
     * This relationship links only by 'school_id'. You MUST ensure the 'syear'
     * context is handled correctly in your queries when using this relation.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    /**
     * Get the student that this report card belongs to.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    /**
     * Get the marking period for this report card.
     */
    public function markingPeriod(): BelongsTo
    {
        // Links 'marking_period_id' on this table to 'marking_period_id' on the 'school_marking_periods' table.
        return $this->belongsTo(SchoolMarkingPeriod::class, 'marking_period_id', 'marking_period_id');
    }

    /**
     * Get the grade level associated with this report card at the time of generation (if any).
     */
    public function gradeLevel(): BelongsTo
    {
        // Links 'grade_id' on this table to 'id' on the 'school_gradelevels' table.
        return $this->belongsTo(GradeLevel::class, 'grade_id', 'id');
    }

    /**
     * Get the staff member who generated this report card (if any).
     */
    public function generator(): BelongsTo
    {
        // Links 'generated_by' on this table to 'staff_id' on the 'staff' table.
        // Ensure the Staff model's primary key is indeed 'staff_id'.
        return $this->belongsTo(Staff::class, 'generated_by', 'staff_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include published report cards.
     * Usage: ReportCard::published()->get();
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePublished(Builder $query): Builder // Added type hint for Builder
    {
        return $query->where('is_published', true);
    }

    // Add other scopes as needed, e.g., for specific marking periods, students, etc.
     public function scopeForMarkingPeriod(Builder $query, int $markingPeriodId): Builder
     {
         return $query->where('marking_period_id', $markingPeriodId);
     }
}
