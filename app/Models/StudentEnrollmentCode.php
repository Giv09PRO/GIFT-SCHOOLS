<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder; // Import Builder for scopes
use Illuminate\Database\Eloquent\Collection as EloquentCollection; // Alias Eloquent Collection

/**
 * Represents a code used for describing student enrollment or withdrawal reasons.
 *
 * Corresponds to the 'student_enrollment_codes' table.
 *
 * @method static Builder|StudentEnrollmentCode newModelQuery()
 * @method static Builder|StudentEnrollmentCode newQuery()
 * @method static Builder|StudentEnrollmentCode query()
 * @method static Builder|StudentEnrollmentCode where(string $column, mixed $value)
 * @method static Builder|StudentEnrollmentCode whereId($value)
 * @method static Builder|StudentEnrollmentCode whereSyear(int $syear)
 * @method static Builder|StudentEnrollmentCode whereType(string $type)
 * @method static Builder|StudentEnrollmentCode ofType(string $type) Scope for specific type ('add' or 'drop').
 * @method static Builder|StudentEnrollmentCode isDefault() Scope for default codes.
 * @method static Builder|StudentEnrollmentCode forYear(int $syear) Scope for a specific school year.
 *
 * @property int $id The unique identifier for the enrollment code.
 * @property int $syear The school year this code applies to.
 * @property string $title The full description of the code (e.g., "Transfer from another district").
 * @property string|null $short_name An optional short name for the code.
 * @property string|null $type The type of code, likely 'add' for enrollment or 'drop' for withdrawal.
 * @property bool|null $default_code Flag indicating if this is a default code (VARCHAR(1) cast to boolean).
 * @property int|null $sort_order Optional sort order for listing codes.
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read EloquentCollection|StudentEnrollment[] $enrollmentsUsingThisCode Enrollments using this code as the enrollment reason.
 * @property-read EloquentCollection|StudentEnrollment[] $withdrawalsUsingThisCode Withdrawals using this code as the drop reason.
 */
class StudentEnrollmentCode extends Model
{
    use HasFactory; // Enables factory support

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_enrollment_codes';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id'; // Default 'id' is fine

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
        'title',
        'short_name',
        'type', // e.g., 'add', 'drop'
        'default_code', // e.g., 'Y', 'N', '1', '0'
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'syear' => 'integer', // DECIMAL(4,0) -> integer
        'sort_order' => 'integer', // DECIMAL(10,0) -> integer
        // Cast VARCHAR(1) like 'Y'/'N' or '1'/'0' to boolean. Adjust if storage format differs.
        'default_code' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the student enrollment records where this code was used as the enrollment reason.
     */
    public function enrollmentsUsingThisCode(): HasMany
    {
        // Links 'id' on this table to 'enrollment_code' on the 'student_enrollment' table.
        return $this->hasMany(StudentEnrollment::class, 'enrollment_code', 'id');
    }

    /**
     * Get the student enrollment records where this code was used as the drop/withdrawal reason.
     */
    public function withdrawalsUsingThisCode(): HasMany
    {
        // Links 'id' on this table to 'drop_code' on the 'student_enrollment' table.
        return $this->hasMany(StudentEnrollment::class, 'drop_code', 'id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include codes of a specific type ('add' or 'drop').
     * Usage: StudentEnrollmentCode::ofType('add')->get();
     *
     * @param Builder $query
     * @param string $type The type to filter by (e.g., 'add', 'drop').
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include default codes.
     * Usage: StudentEnrollmentCode::isDefault()->get();
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsDefault(Builder $query): Builder
    {
        // Assumes 'default_code' column stores '1' or 'Y' for true.
        // The boolean cast handles the conversion.
        return $query->where('default_code', true);
    }

    /**
     * Scope a query to only include codes for a specific school year.
     * Usage: StudentEnrollmentCode::forYear(2024)->get();
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
     * Scope a query to order codes by their sort_order field.
     * Usage: StudentEnrollmentCode::orderBySortOrder()->get();
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
