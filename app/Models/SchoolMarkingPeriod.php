<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder; // Import Builder for scopes

/**
 * Represents a marking period (e.g., Quarter 1, Semester 2) for a specific school and year.
 *
 * Corresponds to the 'school_marking_periods' table.
 *
 * @method static Builder|SchoolMarkingPeriod newModelQuery()
 * @method static Builder|SchoolMarkingPeriod newQuery()
 * @method static Builder|SchoolMarkingPeriod query()
 * @method static Builder|SchoolMarkingPeriod where(string $column, mixed $value)
 * @method static Builder|SchoolMarkingPeriod whereMarkingPeriodId($value)
 * @method static Builder|SchoolMarkingPeriod whereSchoolId(int $schoolId)
 * @method static Builder|SchoolMarkingPeriod whereSyear(int $syear)
 * @method static Builder|SchoolMarkingPeriod current() Scope to get marking periods active now.
 * @method static Builder|SchoolMarkingPeriod forYear(int $syear) Scope for a specific school year.
 *
 * @property int $marking_period_id The unique identifier for the marking period.
 * @property int $syear The school year this marking period belongs to.
 * @property string $mp Marking period code (e.g., "Q1", "S1").
 * @property int $school_id Foreign key for the associated school.
 * @property string $title The full title of the marking period (e.g., "First Quarter").
 * @property string|null $short_name An optional short name (e.g., "Q1").
 * @property int|null $sort_order Optional sort order for listing marking periods.
 * @property Carbon $start_date The start date of the marking period.
 * @property Carbon $end_date The end date of the marking period.
 * @property Carbon|null $post_start_date Start date for grade posting (optional).
 * @property Carbon|null $post_end_date End date for grade posting (optional).
 * @property bool|null $does_grades Flag indicating if grades are recorded during this period.
 * @property bool|null $does_comments Flag indicating if comments are recorded during this period.
 * @property int|null $rollover_id ID used during year rollover processes (optional).
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read School $school The associated School model.
 * @property-read Collection|Exam[] $exams Exams scheduled within this marking period.
 * @property-read Collection|ReportCard[] $reportCards Report cards generated for this marking period.
 */
class SchoolMarkingPeriod extends Model
{
    use HasFactory; // Enables factory support

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'school_marking_periods';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'marking_period_id';

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
        'mp',
        'school_id',
        'title',
        'short_name',
        'sort_order',
        'start_date',
        'end_date',
        'post_start_date',
        'post_end_date',
        'does_grades',
        'does_comments',
        'rollover_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'syear' => 'integer', // DECIMAL(4,0) -> integer
        'school_id' => 'integer', // BIGINT UNSIGNED -> integer
        'sort_order' => 'integer', // DECIMAL(10,0) -> integer
        'start_date' => 'date', // DATE -> Carbon date object
        'end_date' => 'date',   // DATE -> Carbon date object
        'post_start_date' => 'date', // DATE -> Carbon date object or null
        'post_end_date' => 'date',   // DATE -> Carbon date object or null
        // Cast VARCHAR(1) like 'Y'/'N' or '1'/'0' to boolean. Adjust if storage format differs.
        'does_grades' => 'boolean',
        'does_comments' => 'boolean',
        'rollover_id' => 'integer', // BIGINT UNSIGNED -> integer or null
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the school that this marking period belongs to.
     * IMPORTANT: The 'schools' table has a composite primary key (id, syear).
     * This relationship links only by 'school_id'. Ensure the 'syear'
     * context is handled correctly in queries.
     */
    public function school(): BelongsTo
    {
        // Links 'school_id' on this table to 'id' on the 'schools' table.
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    /**
     * Get the exams associated with this marking period.
     */
    public function exams(): HasMany
    {
        // Links 'marking_period_id' on this table to 'marking_period_id' on the 'exams' table.
        return $this->hasMany(Exam::class, 'marking_period_id', 'marking_period_id');
    }

    /**
     * Get the report cards associated with this marking period.
     */
    public function reportCards(): HasMany
    {
        // Links 'marking_period_id' on this table to 'marking_period_id' on the 'report_cards' table.
        return $this->hasMany(ReportCard::class, 'marking_period_id', 'marking_period_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include marking periods for a specific school year.
     * Usage: SchoolMarkingPeriod::forYear(2024)->get();
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
     * Scope a query to only include marking periods that are currently active.
     * Usage: SchoolMarkingPeriod::current()->get();
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeCurrent(Builder $query): Builder
    {
        $today = Carbon::today();
        return $query->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);
    }

    /**
     * Scope a query to order marking periods by their sort_order field.
     * Usage: SchoolMarkingPeriod::orderBySortOrder()->get();
     *
     * @param Builder $query
     * @param string $direction 'asc' or 'desc'
     * @return Builder
     */
    public function scopeOrderBySortOrder(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('sort_order', $direction);
    }
}
