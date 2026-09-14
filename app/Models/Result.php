<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder; // Import Builder for scopes
use Illuminate\Support\Carbon; // Import Carbon for type hinting

/**
 * Represents a student's result for a specific subject within an exam.
 *
 * Corresponds to the 'results' table.
 * Note: The unique key is typically (exam_id, student_id, subject_id).
 *
 * @method static Builder|Result newModelQuery()
 * @method static Builder|Result newQuery()
 * @method static Builder|Result query()
 * @method static Builder|Result where(string $column, mixed $value)
 * @method static Builder|Result whereId($value)
 * @method static Builder|Result whereExamId(int $examId)
 * @method static Builder|Result whereStudentId(int $studentId)
 * @method static Builder|Result whereSubjectId(int $subjectId)
 * @method static Builder|Result finalized() Scope to get only finalized results.
 *
 * @property int $id The unique identifier for the result entry.
 * @property int $exam_id Foreign key for the associated exam.
 * @property int $student_id Foreign key for the associated student.
 * @property int $subject_id Foreign key for the associated subject.
 * @property float|null $score The score achieved, typically decimal(5,2).
 * @property string|null $comments Optional comments about the result.
 * @property int|null $graded_by Foreign key for the staff member who graded this result.
 * @property bool $is_finalized Flag indicating if the result is finalized.
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read Exam $exam The associated Exam model.
 * @property-read Student $student The associated Student model.
 * @property-read Subject $subject The associated Subject model.
 * @property-read Staff|null $grader The Staff model representing the grader.
 */
class Result extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'results';

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
     * Added 'subject_id' based on schema.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_id',
        'student_id',
        'subject_id', // Added subject_id
        'score',
        'comments',
        'graded_by',
        'is_finalized',
    ];

    /**
     * The attributes that should be cast to native types.
     * Added 'subject_id' cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exam_id' => 'integer', // BIGINT UNSIGNED -> integer
        'student_id' => 'integer', // BIGINT UNSIGNED -> integer
        'subject_id' => 'integer', // BIGINT UNSIGNED -> integer (Added)
        'score' => 'decimal:2', // DECIMAL(5,2) -> float with 2 decimal places
        'graded_by' => 'integer', // BIGINT UNSIGNED -> integer
        'is_finalized' => 'boolean', // TINYINT(1) -> boolean
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the exam that this result belongs to.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id', 'id');
    }

    /**
     * Get the student that this result belongs to.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    /**
     * Get the subject that this result belongs to.
     * Added relationship based on schema.
     */
    public function subject(): BelongsTo
    {
        // Assumes Subject model exists and its primary key is 'subject_id'
        return $this->belongsTo(Subject::class, 'subject_id', 'subject_id');
    }


    /**
     * Get the staff member who graded this result (if any).
     */
    public function grader(): BelongsTo
    {
        // Links 'graded_by' on this table to 'staff_id' on the 'staff' table.
        // Ensure the Staff model's primary key is indeed 'staff_id'.
        return $this->belongsTo(Staff::class, 'graded_by', 'staff_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include finalized results.
     * Usage: Result::finalized()->get();
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeFinalized(Builder $query): Builder // Added type hint for Builder
    {
        return $query->where('is_finalized', true);
    }

    // Add other scopes as needed, e.g., for specific subjects, score ranges etc.
     public function scopeForSubject(Builder $query, int $subjectId): Builder
     {
         return $query->where('subject_id', $subjectId);
     }
}
