<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder; // Import Builder for scopes
use Illuminate\Support\Carbon; // Import Carbon for type hinting (if timestamps were used)
use Illuminate\Support\Collection; // Import Collection

/**
 * Represents a Grading Scale defined by a school for a specific year.
 *
 * Corresponds to the 'grade_scales' table.
 * Examples: A-F Scale, Percentage Scale, Pass/Fail Scale.
 *
 * @method static Builder|GradeScale newModelQuery()
 * @method static Builder|GradeScale newQuery()
 * @method static Builder|GradeScale query()
 * @method static Builder|GradeScale where(string $column, mixed $value)
 * @method static Builder|GradeScale whereId($value)
 * @method static Builder|GradeScale whereSchoolId(int $schoolId)
 * @method static Builder|GradeScale whereSyear(int $syear)
 * @method static Builder|GradeScale whereIsDefault(bool $isDefault)
 * @method static Builder|GradeScale default() Scope to get the default scale for a school/year context.
 *
 * @property int $id The unique identifier for the grade scale.
 * @property int $school_id Foreign key for the associated school.
 * @property int $syear The school year this scale applies to.
 * @property string $name The name of the grade scale (e.g., "Standard Letter Grades").
 * @property string|null $description An optional description of the scale.
 * @property bool $is_default Flag indicating if this is the default scale for the school/year.
 * // Note: created_at/updated_at are not present in the provided schema for this table.
 *
 * @property-read School $school The associated School model.
 * @property-read Collection|GradeScaleEntry[] $entries The individual entries (grades) within this scale.
 */
class GradeScale extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'grade_scales';

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
    protected $keyType = 'int'; // Or 'bigint'

    /**
     * Indicates if the model should be timestamped.
     * Set to false because the 'grade_scales' table schema provided
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
        'school_id',
        'syear',
        'name',
        'description',
        'is_default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'school_id' => 'integer',
        'syear' => 'integer', // DECIMAL(4,0) -> integer
        'is_default' => 'boolean', // TINYINT(1) -> boolean
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the school that this grade scale belongs to.
     * IMPORTANT: The 'schools' table has a composite primary key (id, syear).
     * This relationship links only by 'school_id'. Ensure the 'syear'
     * context is handled correctly in queries.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    /**
     * Get the entries (specific grades like A, B, C) associated with this scale.
     * Assumes a GradeScaleEntry model exists.
     */
    public function entries(): HasMany
    {
        // Links 'id' on this table to 'grade_scale_id' on the 'grade_scale_entries' table.
        return $this->hasMany(GradeScaleEntry::class, 'grade_scale_id', 'id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include the default grade scale.
     * Note: You'll typically need to combine this with school/year context.
     * Usage: GradeScale::where('school_id', $schoolId)->where('syear', $syear)->default()->first();
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }
}
