<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder; // Import Builder for scopes
use Illuminate\Database\Eloquent\Collection as EloquentCollection; // Alias Eloquent Collection

/**
 * Represents an academic subject taught at a school.
 *
 * Corresponds to the 'subjects' table.
 *
 * @method static Builder|Subject newModelQuery()
 * @method static Builder|Subject newQuery()
 * @method static Builder|Subject query()
 * @method static Builder|Subject where(string $column, mixed $value)
 * @method static Builder|Subject whereSubjectId($value)
 * @method static Builder|Subject whereSchoolId(int $schoolId) // This will be ambiguous if not qualified in scope
 * @method static Builder|Subject whereSyear(int $syear)       // This will be ambiguous if not qualified in scope
 * @method static Builder|Subject forYear(int $syear) Scope for a specific school year.
 * @method static Builder|Subject forSchool(int $schoolId) Scope for a specific school.
 *
 * @property int $subject_id The unique identifier for the subject.
 * @property int $syear The school year this subject definition applies to.
 * @property int $school_id Foreign key for the associated school.
 * @property string $title The full title of the subject (e.g., "Mathematics", "English Language Arts").
 * @property string|null $short_name An optional short name for the subject (e.g., "MATH", "ELA").
 * @property int|null $sort_order Optional sort order for listing subjects.
 * @property int|null $rollover_id ID used during year rollover processes (optional).
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read School $school The associated School model.
 * @property-read EloquentCollection|Exam[] $exams Exams associated with this subject via the pivot table.
 * @property-read EloquentCollection|GradeLevel[] $gradeLevels Grade levels where this subject is taught via the pivot table.
 * @property-read EloquentCollection|Result[] $results Results recorded for this subject.
 * @property-read EloquentCollection|TeacherSubjectGrade[] $teacherAssignments Assignments linking teachers to this subject.
 */
class Subject extends Model
{
    use HasFactory; // Enables factory support

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'subjects';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'subject_id';

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
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'syear',
        'school_id',
        'title',
        'short_name',
        'sort_order',
        'rollover_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'syear' => 'integer',
        'school_id' => 'integer',
        'sort_order' => 'integer',
        'rollover_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_subjects', 'subject_id', 'exam_id')
            ->withTimestamps();
    }

    public function gradeLevels(): BelongsToMany
    {
        return $this->belongsToMany(GradeLevel::class, 'grade_subjects', 'subject_id', 'grade_id')
            ->withPivot('syear', 'school_id')
            ->withTimestamps();
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'subject_id', 'subject_id');
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectGrade::class, 'subject_id', 'subject_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include subjects for a specific school year.
     *
     * @param Builder $query
     * @param int $year
     * @return Builder
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        // CRITICAL FIX: Qualify syear with the table name
        return $query->where('subjects.syear', $year);
    }

    /**
     * Scope a query to only include subjects for a specific school.
     *
     * @param Builder $query
     * @param int $schoolId
     * @return Builder
     */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        // CRITICAL FIX: Qualify school_id with the table name
        return $query->where('subjects.school_id', $schoolId);
    }

    /**
     * Scope a query to order subjects by their sort_order field.
     *
     * @param Builder $query
     * @param string $direction 'asc' or 'desc'
     * @return Builder
     */
    public function scopeOrderBySortOrder(Builder $query, string $direction = 'asc'): Builder
    {
        // Qualify sort_order for robustness
        return $query->orderByRaw('ISNULL(subjects.sort_order) ' . $direction . ', subjects.sort_order ' . $direction);
    }
}
