<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'exams';

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
    protected $keyType = 'bigint';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true; // Corresponds to created_at and updated_at

    /**
     * The attributes that are mass assignable.
     *
     * Note:
     * - 'subject_id' was removed from fillable as exams are linked to subjects via a pivot table (exam_subjects).
     * - 'exam_type' was removed as the table has a 'type' column for this.
     * - 'exam_date' was changed to 'exam_start_date' to match the table schema.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'syear',
        'school_id',
        'marking_period_id',
        'type', // 'midterm', 'final', 'quiz'
        'description',
        'weight',
        'exam_start_date', // Changed from exam_date
        'exam_end_date',   // Added to match schema
        'start_time',
        'end_time',
        'duration_minutes',
        'status',
        'created_by',
        'is_published',
        'max_score',
        'instructions',
        'gradelevel_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * Note:
     * - 'subject_id' cast removed.
     * - 'exam_date' cast changed to 'exam_start_date'.
     * - 'exam_end_date' cast added.
     * - 'start_time' and 'end_time' are typically stored as TIME in SQL,
     * Laravel can handle them as strings or Carbon objects if cast to 'datetime:H:i:s' or just 'datetime'.
     * If your DB column is TIME, string is often fine, or use a custom accessor/mutator for Carbon.
     * For simplicity, I'm keeping H:i:s, but ensure it matches your DB interaction needs.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'syear' => 'decimal:0',
        'school_id' => 'integer',
        'marking_period_id' => 'integer',
        'weight' => 'decimal:2',
        'exam_start_date' => 'date',
        'exam_end_date' => 'date',
        // 'start_time' => 'datetime:H:i:s', // Or 'string' if DB type is TIME and you handle it as string
        // 'end_time' => 'datetime:H:i:s',   // Or 'string'
        'duration_minutes' => 'integer',
        'created_by' => 'integer',
        'is_published' => 'boolean',
        'max_score' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'gradelevel_id' => 'integer',
    ];

    /**
     * Get the school that this exam belongs to.
     */
    public function school(): BelongsTo
    {
        // Assuming School model has composite primary key (id, syear) as per schema
        // If School model's primary key is just 'id', then this is fine:
        // return $this->belongsTo(School::class, 'school_id', 'id');
        // However, the `schools` table has PRIMARY KEY (`id`,`syear`).
        // Laravel's default BelongsTo won't work directly with composite keys on the parent.
        // You might need to adjust how you query or structure this if strict Eloquent relations are needed.
        // For now, assuming School model primarily uses 'id' for relations or you handle syear contextually.
        return $this->belongsTo(School::class, 'school_id', 'id');
        // If you consistently query schools with syear, you might add a scope or query constraint:
        // Example: return $this->belongsTo(School::class, 'school_id', 'id')->where('syear', $this->syear);
        // This requires the School model to be aware of 'syear' in its queries.
    }

    /**
     * Get the marking period that this exam belongs to.
     */
    public function markingPeriod(): BelongsTo
    {
        return $this->belongsTo(SchoolMarkingPeriod::class, 'marking_period_id', 'marking_period_id');
    }

    /**
     * The subjects associated with this exam.
     * This is a Many-to-Many relationship through the 'exam_subjects' pivot table.
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'exam_subjects', 'exam_id', 'subject_id')
            ->withTimestamps(); // If your pivot table has created_at/updated_at
    }

    /**
     * Get the staff member (creator) who created this exam.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by', 'staff_id');
    }

    /**
     * Get all the results for this exam.
     */
    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'exam_id', 'id');
    }

    /**
     * Get the grade level this exam is for.
     */
    public function gradeLevel(): BelongsTo
    {
        // The foreign key in 'exams' table is 'gradelevel_id'
        // The primary key in 'school_gradelevels' table is 'id'
        return $this->belongsTo(GradeLevel::class, 'gradelevel_id', 'id');
    }

    // --- Scopes ---

    /**
     * Scope a query to only include published exams.
     * @param Builder $query
     * @return Builder
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope a query to only include exams of a specific type.
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include exams for a specific school year.
     * @param Builder $query
     * @param string|int $syear
     * @return Builder
     */
    public function scopeForYear(Builder $query, $syear): Builder
    {
        return $query->where('syear', $syear);
    }

    /**
     * Scope a query to only include exams for a specific school.
     * @param Builder $query
     * @param int $schoolId
     * @return Builder
     */
    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }
}
