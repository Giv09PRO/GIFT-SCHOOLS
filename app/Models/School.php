<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Represents a School entity within the application.
 *
 * Corresponds to the 'schools' table.
 * Note: The database table uses a composite primary key (`id`, `syear`).
 * Eloquent primarily uses the 'id' for lookups by default. Ensure the 'syear' context
 * is managed appropriately in queries and relationships involving this model.
 *
 * @method static Builder|School newModelQuery()
 * @method static Builder|School newQuery()
 * @method static Builder|School query()
 * @method static Builder|School where(string $column, mixed $value)
 * @method static Builder|School whereId($value)
 * @method static Builder|School whereSyear(int $year)
 * @method static Builder|School findOrFail(int $id) // Standard findOrFail uses primary key 'id'
 * @method static Builder|School find(int $id)      // Standard find uses primary key 'id'
 * @method static Builder|School select(string ...$columns)
 * @method static Builder|School orderBy(string $column, string $direction = 'asc')
 *
 * @property int $id The unique auto-incrementing identifier (used by Eloquent).
 * @property int $syear The school year this record pertains to.
 * @property string $title The full name of the school.
 * @property string|null $address The street address of the school.
 * @property string|null $city The city where the school is located.
 * @property string|null $state The state or region.
 * @property string|null $zipcode The postal code.
 * @property string|null $phone The main phone number.
 * @property string|null $principal The name of the school principal.
 * @property string|null $www_address The school's website address.
 * @property string|null $school_number An official school identification number.
 * @property string|null $short_name A short abbreviation for the school name.
 * @property float|null $reporting_gp_scale The reporting grade point scale (decimal:3).
 * @property int|null $number_days_rotation The number of days in a schedule rotation.
 * @property array|null $settings JSON column for storing various school-specific settings (cast to array).
 * @property Carbon|null $created_at Timestamp when the record was created.
 * @property Carbon|null $updated_at Timestamp when the record was last updated.
 *
 * @property-read Collection|Staff[] $staff Staff members currently associated with this school ID.
 * @property-read Collection|GradeLevel[] $gradeLevels Grade levels defined for this school ID.
 * @property-read Collection|StudentEnrollment[] $enrollments Student enrollments associated with this school ID and syear.
 * @property-read Collection|SchoolMarkingPeriod[] $markingPeriods Marking periods defined for this school ID and syear.
 * @property-read Collection|SchoolPeriod[] $periods School periods defined for this school ID and syear.
 * @property-read Collection|Subject[] $subjects Subjects defined for this school ID and syear.
 * @property-read Collection|Exam[] $exams Exams scheduled for this school ID and syear.
 * @property-read Collection|Fee[] $billingFees Billing fees issued by this school ID for this syear.
 * @property-read Collection|Payment[] $billingPayments Billing payments recorded for this school ID for this syear.
 * @property-read Collection|ReportCard[] $reportCards Report cards generated for this school ID and syear.
 * @property-read Collection|GradeScale[] $gradeScales Grade scales defined for this school ID and syear.
 * @property-read Collection|MarkingScale[] $markingScales Marking scales defined for this school ID and syear.
 * @property-read Collection|TeacherSubjectGrade[] $teacherAssignments Teacher assignments for this school ID and syear.
 */
class School extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'schools';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The primary key for the model.
     * Eloquent uses 'id' by default for operations like find(), findOrFail().
     * The actual DB primary key is composite (`id`, `syear`).
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int'; // or 'bigint'

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'syear',
        'title',
        'address',
        'city',
        'state',
        'zipcode',
        'phone',
        'principal',
        'www_address',
        'school_number',
        'short_name',
        'reporting_gp_scale',
        'number_days_rotation',
        'settings' // Added settings
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'syear' => 'integer', // DECIMAL(4,0) -> integer
        'reporting_gp_scale' => 'decimal:3', // DECIMAL(10,3)
        'number_days_rotation' => 'integer', // DECIMAL(1,0) -> integer
        'settings' => 'array', // JSON -> array
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the staff members associated with the school (based on current_school_id).
     * Note: This links only by 'id'. The 'syear' context of the staff member might be different.
     */
    public function staff(): HasMany
    {
        // Links 'id' on schools table to 'current_school_id' on staff table.
        return $this->hasMany(Staff::class, 'current_school_id', 'id');
    }

    /**
     * Get the grade levels associated with the school for the specific school year.
     * This relationship correctly uses both school_id and school_syear from the GradeLevel model.
     */
    public function gradeLevels(): HasMany
    {
        // Links 'id' on schools table to 'school_id' on school_gradelevels table.
        // Assumes GradeLevel model handles the 'school_syear' constraint.
        return $this->hasMany(GradeLevel::class, 'school_id', 'id')
            ->where('school_syear', $this->syear); // Add constraint for the year
    }

    /**
     * Get the student enrollments associated with the school for the specific school year.
     */
    public function enrollments(): HasMany
    {
        // Links 'id' on schools table to 'school_id' on student_enrollment table.
        return $this->hasMany(StudentEnrollment::class, 'school_id', 'id')
            ->where('syear', $this->syear); // Add constraint for the year
    }

    /**
     * Get the marking periods defined for this school and year.
     */
    public function markingPeriods(): HasMany
    {
        // Links 'id' on schools table to 'school_id' on school_marking_periods table.
        return $this->hasMany(SchoolMarkingPeriod::class, 'school_id', 'id')
            ->where('syear', $this->syear); // Add constraint for the year
    }

    /**
     * Get the school periods defined for this school and year.
     */
    public function periods(): HasMany
    {
        // Links 'id' on schools table to 'school_id' on school_periods table.
        return $this->hasMany(SchoolPeriod::class, 'school_id', 'id')
            ->where('syear', $this->syear); // Add constraint for the year
    }

    /**
     * Get the subjects defined for this school and year.
     */
    public function subjects(): HasMany
    {
        // Links 'id' on schools table to 'school_id' on subjects table.
        return $this->hasMany(Subject::class, 'school_id', 'id')
            ->where('syear', $this->syear); // Add constraint for the year
    }

    /**
     * Get the exams scheduled for this school and year.
     */
    public function exams(): HasMany
    {
        // Links 'id' on schools table to 'school_id' on exams table.
        return $this->hasMany(Exam::class, 'school_id', 'id')
            ->where('syear', $this->syear); // Add constraint for the year
    }

    /**
     * Get the billing fees issued by this school for this year.
     */
    public function billingFees(): HasMany
    {
        // Links 'id' on schools table to 'school_id' on billing_fees table.
        return $this->hasMany(Fee::class, 'school_id', 'id') // Assuming Fee model maps to billing_fees
        ->where('syear', $this->syear); // Add constraint for the year
    }

    /**
     * Get the billing payments recorded for this school for this year.
     */
    public function billingPayments(): HasMany
    {
        // Links 'id' on schools table to 'school_id' on billing_payments table.
        return $this->hasMany(Payment::class, 'school_id', 'id') // Assuming Payment model maps to billing_payments
        ->where('syear', $this->syear); // Add constraint for the year
    }

    /**
     * Get the report cards generated for this school and year.
     */
    public function reportCards(): HasMany
    {
        // Links 'id' on schools table to 'school_id' on report_cards table.
        return $this->hasMany(ReportCard::class, 'school_id', 'id')
            ->where('syear', $this->syear); // Add constraint for the year
    }

    /**
     * Get the grade scales defined for this school and year.
     */
    public function gradeScales(): HasMany
    {
        // Links 'id' on schools table to 'school_id' on grade_scales table.
        return $this->hasMany(GradeScale::class, 'school_id', 'id')
            ->where('syear', $this->syear); // Add constraint for the year
    }

    /**
     * Get the marking scales defined for this school and year.
     */
    public function markingScales(): HasMany
    {
        // Links 'id' on schools table to 'school_id' on marking_scales table.
        return $this->hasMany(MarkingScale::class, 'school_id', 'id')
            ->where('syear', $this->syear); // Add constraint for the year
    }

    /**
     * Get the teacher assignments for this school and year.
     */
    public function teacherAssignments(): HasMany
    {
        // Links 'id' on schools table to 'school_id' on teacher_subject_grade table.
        // Assumes TeacherSubjectGrade model exists.
        return $this->hasMany(TeacherSubjectGrade::class, 'school_id', 'id')
            ->where('syear', $this->syear); // Add constraint for the year
    }


    // =========================================================================
    // Settings Helper Methods
    // =========================================================================

    /**
     * Get a specific setting value for the school using dot notation.
     * Provides a default value if the setting is not found.
     *
     * @param string $key The setting key (e.g., 'attendance.absent_code', 'portal.enabled')
     * @param mixed $default The default value to return if the key doesn't exist.
     * @return mixed
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        // Ensure 'settings' attribute is treated as an array, even if null in DB
        return Arr::get($this->settings ?? [], $key, $default);
    }

    /**
     * Set a specific setting value in memory (does not save).
     * Use $school->save() afterwards to persist the change.
     * Allows dot notation.
     *
     * @param string $key The setting key (e.g., 'portal.enabled')
     * @param mixed $value The value to set.
     * @return void
     */
    public function setSetting(string $key, mixed $value): void
    {
        $settings = $this->settings ?? []; // Get current settings or initialize as empty array
        Arr::set($settings, $key, $value); // Set value using dot notation
        $this->settings = $settings; // Assign back to the attribute (marks model as dirty)
    }

    /**
     * Update multiple settings at once using dot notation keys and save immediately.
     *
     * @param array $newSettings Key-value pairs of settings to update (e.g., ['portal.enabled' => true])
     * @return bool Result of the save operation.
     */
    public function updateSettings(array $newSettings): bool
    {
        $currentSettings = $this->settings ?? [];
        foreach ($newSettings as $key => $value) {
            Arr::set($currentSettings, $key, $value);
        }
        $this->settings = $currentSettings;
        return $this->save(); // Persist changes
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to only include schools for a specific year.
     * Usage: School::year(2024)->get();
     *
     * @param Builder $query
     * @param int $year
     * @return Builder
     */
    public function scopeYear(Builder $query, int $year): Builder
    {
        return $query->where('syear', $year);
    }
}
