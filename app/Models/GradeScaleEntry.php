<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder; // Import Builder

/**
 * Represents a single entry within a GradeScale (e.g., 'A' = 90-100).
 *
 * Corresponds to the 'grade_scale_entries' table.
 *
 * @method static Builder|GradeScaleEntry newModelQuery()
 * @method static Builder|GradeScaleEntry newQuery()
 * @method static Builder|GradeScaleEntry query()
 * @method static Builder|GradeScaleEntry where(string $column, mixed $value)
 * @method static Builder|GradeScaleEntry whereId($value)
 * @method static Builder|GradeScaleEntry whereGradeScaleId(int $gradeScaleId)
 * @method static Builder|GradeScaleEntry whereGradeSymbol(string $symbol)
 *
 * @property int $id The unique identifier for the grade scale entry.
 * @property int $grade_scale_id Foreign key for the associated GradeScale.
 * @property string $grade_symbol The symbol for this grade entry (e.g., "A", "B+", "Pass").
 * @property float $min_score The minimum score required for this grade entry.
 * @property float $max_score The maximum score allowed for this grade entry.
 * @property string|null $description An optional description for this grade entry.
 * // Note: created_at/updated_at are not present in the provided schema for this table.
 *
 * @property-read GradeScale $gradeScale The parent GradeScale model this entry belongs to.
 */
class GradeScaleEntry extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'grade_scale_entries';

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
     * Set to false because the 'grade_scale_entries' table schema provided
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
        'grade_scale_id',
        'grade_symbol',
        'min_score',
        'max_score',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'grade_scale_id' => 'integer', // BIGINT UNSIGNED -> integer
        'min_score' => 'decimal:2', // DECIMAL(5,2) -> float with 2 decimals
        'max_score' => 'decimal:2', // DECIMAL(5,2) -> float with 2 decimals
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the parent GradeScale that this entry belongs to.
     */
    public function gradeScale(): BelongsTo
    {
        // Links 'grade_scale_id' on this table to 'id' on the 'grade_scales' table.
        return $this->belongsTo(GradeScale::class, 'grade_scale_id', 'id');
    }

    // =========================================================================
    // Scopes (Optional examples)
    // =========================================================================

    /**
     * Scope a query to find the entry corresponding to a specific score.
     * Usage: GradeScaleEntry::where('grade_scale_id', $scaleId)->forScore(85)->first();
     *
     * @param Builder $query
     * @param float $score The score to check against min/max range.
     * @return Builder
     */
    public function scopeForScore(Builder $query, float $score): Builder
    {
        return $query->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score);
    }
}
