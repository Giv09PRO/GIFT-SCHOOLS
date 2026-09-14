<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder; // Import Builder

/**
 * Represents the assignment of a teacher to a specific subject and grade level
 * for a given school and year.
 *
 * Corresponds to the 'teacher_subject_grade' pivot/association table.
 *
 * @method static Builder|TeacherSubjectGrade newModelQuery()
 * @method static Builder|TeacherSubjectGrade newQuery()
 * @method static Builder|TeacherSubjectGrade query()
 * @method static Builder|TeacherSubjectGrade where(string $column, mixed $value)
 * @method static Builder|TeacherSubjectGrade whereId($value)
 * @method static Builder|TeacherSubjectGrade whereStaffId(int $staffId)
 * @method static Builder|TeacherSubjectGrade whereSubjectId(int $subjectId)
 * @method static Builder|TeacherSubjectGrade whereGradelevelId(int $gradeLevelId)
 * @method static Builder|TeacherSubjectGrade whereSchoolId(int $schoolId)
 * @method static Builder|TeacherSubjectGrade whereSyear(int $syear)
 *
 * @property int $id The unique identifier for the assignment record.
 * @property int $staff_id Foreign key for the assigned Staff (teacher).
 * @property int $subject_id Foreign key for the assigned Subject.
 * @property int $gradelevel_id Foreign key for the assigned GradeLevel.
 * @property int $syear The school year of the assignment.
 * @property int $school_id Foreign key for the associated School.
 * // Note: created_at/updated_at are not present in the provided schema for this table.
 *
 * @property-read Staff $staff The assigned Staff (teacher) model.
 * @property-read Subject $subject The assigned Subject model.
 * @property-read GradeLevel $gradeLevel The assigned GradeLevel model.
 * @property-read School $school The associated School model.
 */
class TeacherSubjectGrade extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'teacher_subject_grade';

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
    protected $keyType = 'int'; // Or 'bigint'

    /**
     * Indicates if the model should be timestamped.
     * Set to false because the 'teacher_subject_grade' table schema provided
     * does not include 'created_at' and 'updated_at' columns.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'staff_id',
        'subject_id',
        'gradelevel_id',
        'syear',
        'school_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'staff_id' => 'integer', // BIGINT UNSIGNED -> integer
        'subject_id' => 'integer', // BIGINT UNSIGNED -> integer
        'gradelevel_id' => 'integer', // BIGINT UNSIGNED -> integer
        'syear' => 'integer', // DECIMAL(4,0) -> integer
        'school_id' => 'integer', // BIGINT UNSIGNED -> integer
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the Staff (teacher) associated with this assignment.
     */
    public function staff(): BelongsTo
    {
        // Links 'staff_id' on this table to 'staff_id' on the 'staff' table.
        // Ensure Staff model primary key is 'staff_id'.
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }

    /**
     * Get the Subject associated with this assignment.
     */
    public function subject(): BelongsTo
    {
        // Links 'subject_id' on this table to 'subject_id' on the 'subjects' table.
        // Ensure Subject model primary key is 'subject_id'.
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }

    /**
     * Get the GradeLevel associated with this assignment.
     */
    public function gradeLevel(): BelongsTo
    {
        // Links 'gradelevel_id' on this table to 'id' on the 'school_gradelevels' table.
        return $this->belongsTo(GradeLevel::class, 'gradelevel_id', 'id');
    }

    /**
     * Get the School associated with this assignment.
     * IMPORTANT: The 'schools' table has a composite primary key (id, syear).
     * This relationship links only by 'school_id'. Ensure the 'syear'
     * context is handled correctly in queries.
     */
    public function school(): BelongsTo
    {
        // Links 'school_id' on this table to 'id' on the 'schools' table.
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    // =========================================================================
    // Scopes (Optional examples)
    // =========================================================================

    /**
     * Scope a query to only include assignments for a specific school year.
     * Usage: TeacherSubjectGrade::forYear(2024)->get();
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
     * Scope a query to only include assignments for a specific teacher.
     * Usage: TeacherSubjectGrade::forTeacher($staffId)->get();
     *
     * @param Builder $query
     * @param int $staffId
     * @return Builder
     */
    public function scopeForTeacher(Builder $query, int $staffId): Builder
    {
        return $query->where('staff_id', $staffId);
    }
}
