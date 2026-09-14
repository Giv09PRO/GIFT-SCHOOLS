<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Import HasFactory
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon; // Import Carbon for type hinting
use Illuminate\Database\Eloquent\Builder; // Import Builder for scopes

/**
 * Represents a specific period within a school's daily schedule (e.g., Period 1, Homeroom).
 *
 * Corresponds to the 'school_periods' table.
 *
 * @method static Builder|SchoolPeriod newModelQuery()
 * @method static Builder|SchoolPeriod newQuery()
 * @method static Builder|SchoolPeriod query()
 * @method static Builder|SchoolPeriod where(string $column, mixed $value)
 * @method static Builder|SchoolPeriod wherePeriodId($value)
 * @method static Builder|SchoolPeriod whereSchoolId(int $schoolId)
 * @method static Builder|SchoolPeriod whereSyear(int $syear)
 * @method static Builder|SchoolPeriod forYear(int $syear) Scope for a specific school year.
 * @method static Builder|SchoolPeriod takesAttendance() Scope for periods where attendance is taken.
 *
 * @property int $period_id The unique identifier for the school period.
 * @property int $syear The school year this period definition applies to.
 * @property int $school_id Foreign key for the associated school.
 * @property int|null $sort_order Optional sort order for listing periods.
 * @property string $title The full title of the period (e.g., "Period 1", "Lunch").
 * @property string|null $short_name An optional short name (e.g., "P1").
 * @property int|null $length The duration of the period in minutes (optional).
 * @property string|null $start_time The start time of the period (e.g., "08:00"). Stored as VARCHAR.
 * @property string|null $end_time The end time of the period (e.g., "08:45"). Stored as VARCHAR.
 * @property string|null $block Block identifier if using block scheduling (e.g., "A", "B"). Stored as VARCHAR.
 * @property bool|null $attendance Flag indicating if attendance is taken during this period (VARCHAR(1) cast to boolean).
 * @property int|null $rollover_id ID used during year rollover processes (optional).
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read School $school The associated School model.
 */
class SchoolPeriod extends Model
{
    use HasFactory; // Enables factory support

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'school_periods';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'period_id'; // Correct primary key

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
        'school_id',
        'sort_order',
        'title',
        'short_name',
        'length',
        'start_time', // Stored as VARCHAR(10) e.g., "HH:MM"
        'end_time',   // Stored as VARCHAR(10) e.g., "HH:MM"
        'block',      // Stored as VARCHAR(10)
        'attendance', // Stored as VARCHAR(1) e.g., "Y"/"N", "1"/"0"
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
        'length' => 'integer', // INT -> integer
        // start_time and end_time are VARCHAR, keep as string unless specific casting needed
        // 'start_time' => 'datetime:H:i', // Example if you want Carbon objects (ensure format matches)
        // 'end_time' => 'datetime:H:i',   // Example if you want Carbon objects (ensure format matches)
        'attendance' => 'boolean', // Cast VARCHAR(1) like 'Y'/'N' or '1'/'0' to boolean
        'rollover_id' => 'integer', // BIGINT UNSIGNED -> integer or null
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the school that this period belongs to.
     * IMPORTANT: The 'schools' table has a composite primary key (id, syear).
     * This relationship links only by 'school_id'. Ensure the 'syear'
     * context is handled correctly in queries.
     */
    public function school(): BelongsTo
    {
        // Links 'school_id' on this table to 'id' on the 'schools' table.
        return $this->belongsTo(School::class, 'school_id', 'id');
    }

    // Add other relationships if necessary, e.g., to schedules or attendance records

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include periods for a specific school year.
     * Usage: SchoolPeriod::forYear(2024)->get();
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
     * Scope a query to only include periods where attendance is taken.
     * Usage: SchoolPeriod::takesAttendance()->get();
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeTakesAttendance(Builder $query): Builder
    {
        // Assumes 'attendance' column stores '1' or 'Y' for true.
        // The boolean cast handles the conversion.
        return $query->where('attendance', true);
    }

    /**
     * Scope a query to order periods by their sort_order field.
     * Usage: SchoolPeriod::orderBySortOrder()->get();
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
