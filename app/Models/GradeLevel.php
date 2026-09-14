<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection; // Import Collection
use Carbon\Carbon; // Import Carbon

/**
 * Represents a specific grade level within a school for a given school year.
 *
 * Corresponds to the 'school_gradelevels' table.
 *
 * @method static Builder|GradeLevel newModelQuery()
 * @method static Builder|GradeLevel newQuery()
 * @method static Builder|GradeLevel query()
 * @method static Builder|GradeLevel where(string $column, mixed $value)
 * @method static Builder|GradeLevel whereId($value)
 * @method static Builder|GradeLevel whereSchoolId($value)
 * @method static Builder|GradeLevel whereSchoolSyear($value)
 * @method static Builder|GradeLevel whereTitle($value)
 *
 * @property int $id The unique identifier for the grade level.
 * @property int $school_id Foreign key for the associated school.
 * @property int $school_syear The school year this grade level applies to.
 * @property string|null $short_name An optional short name for the grade level.
 * @property string $title The full title of the grade level (e.g., "Grade 1", "Kindergarten").
 * @property int|null $next_grade_id Foreign key for the next grade level in sequence (for promotion).
 * @property int|null $sort_order Optional sort order for listing grade levels.
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read School $school The associated School model.
 * @property-read GradeLevel|null $nextGradeLevel The next GradeLevel model in sequence.
 * @property-read Collection|StudentEnrollment[] $enrollments Collection of student enrollments for this grade level.
 * @property-read Collection|Subject[] $subjects Collection of subjects taught at this grade level.
 * @property-read string|null $next_grade_name Accessor for the title of the next grade level.
 */
class GradeLevel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'school_gradelevels';

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
    protected $keyType = 'int'; // Changed to int based on typical Laravel usage, bigint works too

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
    public $timestamps = true; // Matches created_at/updated_at columns

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'school_syear',
        'short_name',
        'title',
        'next_grade_id',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'school_id' => 'integer',
        'school_syear' => 'integer', // Cast syear to integer
        'next_grade_id' => 'integer',
        'sort_order' => 'integer', // Cast sort_order to integer
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the school that this grade level belongs to.
     * IMPORTANT: The 'schools' table has a composite primary key (id, syear).
     * Standard Eloquent belongsTo work best with single-column keys on the parent.
     * This relationship links only by 'school_id'. You MUST ensure the 'school_syear'
     * context is handled correctly in your queries when using this relation.
     * Example: GradeLevel::where('school_syear', $currentYear)->with('school')->get();
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    /**
     * Get the next grade level in the sequence (for promotion).
     * This is a self-referencing relationship.
     */
    public function nextGradeLevel(): BelongsTo
    {
        // Links 'next_grade_id' on this model to 'id' on another GradeLevel model.
        return $this->belongsTo(GradeLevel::class, 'next_grade_id', 'id');
    }

    /**
     * Get the student enrollments associated with this grade level.
     */
    public function enrollments(): HasMany
    {
        // Assumes StudentEnrollment model exists and uses 'grade_id' as the foreign key.
        return $this->hasMany(StudentEnrollment::class, 'grade_id', 'id');
    }

    /**
     * Get the subjects taught at this grade level.
     * This defines the Many-to-Many relationship via the 'grade_subjects' pivot table.
     */
    public function subjects(): BelongsToMany
    {
        // Pivot table: 'grade_subjects'
        // Foreign key on pivot linking back to this model (GradeLevel): 'grade_id'
        // Foreign key on pivot linking to the related model (Subject): 'subject_id'
        return $this->belongsToMany(Subject::class, 'grade_subjects', 'grade_id', 'subject_id')
            ->wherePivot('syear', $this->school_syear) // Ensure pivot relation matches the grade's year
            ->wherePivot('school_id', $this->school_id) // Ensure pivot relation matches the grade's school
            ->withTimestamps(); // If your 'grade_subjects' table has timestamps
    }

    // =========================================================================
    // Accessors & Helper Methods
    // =========================================================================

    /**
     * Get the name/title of the next grade level if one is defined.
     * Accessor: $gradeLevel->next_grade_name
     *
     * @return string|null
     */
    public function getNextGradeNameAttribute(): ?string // Changed to accessor convention
    {
        // Eager load 'nextGradeLevel' for efficiency if using this often
        // Example: GradeLevel::with('nextGradeLevel')->find($id);
        if ($this->relationLoaded('nextGradeLevel')) {
            return $this->nextGradeLevel ? $this->nextGradeLevel->title : null;
        }
        // Avoid lazy loading in accessor if possible, return null or load explicitly
        // For simplicity here, we allow lazy load but recommend eager loading.
        return optional($this->nextGradeLevel()->first())->title;

    }
}
