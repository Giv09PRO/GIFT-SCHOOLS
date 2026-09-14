<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Represents a student's enrollment record for a specific school and year.
 *
 * Corresponds to the 'student_enrollment' table.
 *
 * @method static Builder|StudentEnrollment newModelQuery()
 * @method static Builder|StudentEnrollment newQuery()
 * @method static Builder|StudentEnrollment query()
 * @method static Builder|StudentEnrollment where(string $column, mixed $value)
 * @method static Builder|StudentEnrollment whereId($value)
 * @method static Builder|StudentEnrollment whereStudentId(int $studentId)
 * @method static Builder|StudentEnrollment whereSchoolId(int $schoolId)
 * @method static Builder|StudentEnrollment whereSyear(int $syear)
 * @method static Builder|StudentEnrollment active(Carbon|string $date = null) Scope for active enrollments.
 * @method static Builder|StudentEnrollment forYear(int $syear) Scope for a specific school year.
 * @method static Builder|StudentEnrollment forSchool(int $schoolId) Scope for a specific school.
 * @method static join(string $string, string $string1, string $string2, string $string3)
 *
 * @property int $id The unique identifier for the enrollment record.
 * @property int $syear The school year of the enrollment.
 * @property int $school_id Foreign key for the associated school.
 * @property int $student_id Foreign key for the associated student.
 * @property int|null $grade_id Foreign key for the associated grade level.
 * @property Carbon|null $start_date The start date of the enrollment period.
 * @property Carbon|null $end_date The end date of the enrollment period (null if currently enrolled).
 * @property int|null $enrollment_code Foreign key referencing the reason for enrollment (student_enrollment_codes table).
 * @property int|null $drop_code Foreign key referencing the reason for withdrawal (student_enrollment_codes table).
 * @property int|null $next_school Foreign key referencing the school the student is transferring to (likely schools.id).
 * @property int|null $calendar_id Foreign key referencing a specific school calendar (if a calendars table exists).
 * @property int|null $last_school Foreign key referencing the school the student came from (likely schools.id).
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read School $school The associated School model.
 * @property-read Student $student The associated Student model.
 * @property-read GradeLevel|null $grade The associated GradeLevel model (via grade() relationship).
 * @property-read StudentEnrollmentCode|null $enrollmentReason The enrollment code description.
 * @property-read StudentEnrollmentCode|null $dropReason The drop code description.
 * @property-read School|null $nextSchoolModel The School model for the next school.
 * @property-read School|null $lastSchoolModel The School model for the last school.
 */
class StudentEnrollment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_enrollment';

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
        'grade_id',
        'start_date',
        'end_date',
        'enrollment_code', // Foreign key to student_enrollment_codes.id
        'drop_code',       // Foreign key to student_enrollment_codes.id
        'next_school',     // Foreign key to schools.id (presumably)
        'calendar_id',     // Foreign key to a potential calendars table
        'last_school',     // Foreign key to schools.id (presumably)
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'syear' => 'integer', // DECIMAL(4,0) -> integer
        'school_id' => 'integer', // BIGINT UNSIGNED -> integer
        'student_id' => 'integer', // BIGINT UNSIGNED -> integer
        'grade_id' => 'integer', // BIGINT UNSIGNED -> integer or null
        'start_date' => 'date', // DATE -> Carbon date object or null
        'end_date' => 'date',   // DATE -> Carbon date object or null
        'enrollment_code' => 'integer', // INT -> integer or null
        'drop_code' => 'integer',       // INT -> integer or null
        'next_school' => 'integer',     // BIGINT UNSIGNED -> integer or null
        'calendar_id' => 'integer',     // BIGINT UNSIGNED -> integer or null
        'last_school' => 'integer',     // BIGINT UNSIGNED -> integer or null
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the school associated with this enrollment.
     * IMPORTANT: The 'schools' table might have a composite primary key (id, syear).
     * This relationship links by 'school_id' to 'schools.id'.
     * Ensure 'schools.id' uniquely identifies a school entity if this is used broadly.
     * The foreign key constraint `student_enrollment_school_id_syear_foreign`
     * uses both `school_id` and `syear` from this table to reference `schools(id, syear)`.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    /**
     * Get the student associated with this enrollment.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    /**
     * Get the grade level associated with this enrollment.
     * This is referenced as 'grade' in eager loading in ParentController.
     */
    public function grade(): BelongsTo
    {
        // Assumes:
        // 1. GradeLevel model exists (App\Models\GradeLevel)
        // 2. The foreign key in the 'student_enrollment' table is 'grade_id'
        // 3. The primary key in the 'school_gradelevels' table (or your grades table) is 'id'
        return $this->belongsTo(GradeLevel::class, 'grade_id', 'id');
    }

    /**
     * Get the description/reason for the enrollment code.
     * Assumes StudentEnrollmentCode model exists.
     */
    public function enrollmentReason(): BelongsTo
    {
        // Links 'enrollment_code' on this table to 'id' on the 'student_enrollment_codes' table.
        return $this->belongsTo(StudentEnrollmentCode::class, 'enrollment_code', 'id');
    }

    /**
     * Get the description/reason for the drop/withdrawal code.
     * Assumes StudentEnrollmentCode model exists.
     */
    public function dropReason(): BelongsTo
    {
        // Links 'drop_code' on this table to 'id' on the 'student_enrollment_codes' table.
        return $this->belongsTo(StudentEnrollmentCode::class, 'drop_code', 'id');
    }

    /**
     * Get the School model for the 'next_school' ID.
     * Assumes 'next_school' refers to the 'id' column in the 'schools' table.
     */
    public function nextSchoolModel(): BelongsTo
    {
        return $this->belongsTo(School::class, 'next_school', 'id');
    }

    /**
     * Get the School model for the 'last_school' ID.
     * Assumes 'last_school' refers to the 'id' column in the 'schools' table.
     */
    public function lastSchoolModel(): BelongsTo
    {
        return $this->belongsTo(School::class, 'last_school', 'id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include active enrollments as of a specific date (defaults to today).
     * An enrollment is considered active if the date falls between the start_date
     * and end_date, or if the end_date is null.
     *
     * Usage: StudentEnrollment::active()->get();
     * StudentEnrollment::active('2024-01-15')->get();
     *
     * @param Builder $query
     * @param Carbon|string|null $date The date to check against (defaults to today).
     * @return Builder
     */
    public function scopeActive(Builder $query, Carbon|string $date = null): Builder
    {
        // Ensure $date is a Carbon instance, defaulting to today if null
        // Using endOfDay for $checkDate and startOfDay for end_date comparison ensures full day coverage.
        $checkDate = $date ? Carbon::parse($date)->endOfDay() : Carbon::today()->endOfDay();

        return $query->where('start_date', '<=', $checkDate) // Enrollment must have started on or before the check date.
            ->where(function (Builder $q) use ($checkDate) {
                // And either:
                // 1. The enrollment end_date is on or after the start of the check date.
                $q->where('end_date', '>=', $checkDate->copy()->startOfDay())
                  // 2. Or the enrollment end_date is null (meaning it's ongoing).
                  ->orWhereNull('end_date');
            });
    }

    /**
     * Scope a query to only include enrollments for a specific school year.
     * Usage: StudentEnrollment::forYear(2024)->get();
     *
     * @param Builder $query
     * @param int $year
     * @return Builder
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->where('syear', $year);
    }

    /**
     * Scope a query to only include enrollments for a specific school.
     * Usage: StudentEnrollment::forSchool($schoolId)->get();
     *
     * @param Builder $query
     * @param int $schoolId
     * @return Builder
     */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
