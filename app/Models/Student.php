<?php

namespace App\Models;

use App\Helpers\Qs; // Used in comments and potentially in other scopes
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB; // Required for DB::raw if used in other scopes
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\Student
 *
 * Represents a student in the application. This model is authenticatable,
 * allowing students to log in. It includes relationships to enrollments,
 * parents, fees, payments, academic results, and report cards.
 * It also supports soft deletion.
 *
 * @property int $id Unique identifier for the student.
 * @property string $last_name Student's last name.
 * @property string $first_name Student's first name.
 * @property string|null $middle_name Student's middle name.
 * @property string|null $name_suffix Name suffix (e.g., Jr., III).
 * @property string|null $username Login username (typically auto-lowercased).
 * @property string|null $password Hashed password for student login.
 * @property string|null $email Student's email address.
 * @property string|null $phone Student's phone number.
 * @property string|null $remember_token For "remember me" functionality.
 * @property Carbon|null $last_login Timestamp of last successful login.
 * @property int|null $failed_login_attempts Count of consecutive failed login attempts.
 * @property string|null $prem_number Permanent or State assigned ID number.
 * @property Carbon|null $dob Date of birth.
 * @property string|null $gender Student's gender (e.g., Male, Female, Other).
 * @property string|null $address Student's physical address.
 * @property Carbon|null $custom_200000004 Custom date field example.
 * @property string|null $custom_200000006 Custom text field example.
 * @property string|null $custom_200000007 Custom text field example.
 * @property string|null $custom_200000008 Custom text field example.
 * @property string|null $custom_200000009 Custom long text field example.
 * @property bool|null $custom_200000010 Custom char(1) field, potentially a boolean flag (e.g., Y/N).
 * @property string|null $custom_200000011 Custom long text field example.
 * @property Carbon|null $email_verified_at Timestamp for email verification.
 * @property Carbon|null $created_at Timestamp of model creation.
 * @property Carbon|null $updated_at Timestamp of last model update.
 * @property Carbon|null $deleted_at Timestamp of soft deletion.
 *
 * @property-read string $full_name Accessor for the student's full name (e.g., "John Michael Doe Jr.").
 * @property-read EloquentCollection|StudentEnrollment[] $enrollments All enrollment records for the student.
 * @property-read StudentEnrollment|null $currentActiveEnrollment The student's current active enrollment based on `active()` scope.
 * @property-read EloquentCollection|Parents[] $parents Parents or guardians associated with the student.
 * @property-read EloquentCollection|Fee[] $fees Billing fees assigned to the student.
 * @property-read EloquentCollection|Payment[] $payments Payments made by or for the student.
 * @property-read EloquentCollection|Result[] $results Academic results for the student.
 * @property-read EloquentCollection|ReportCard[] $reportCards Report cards generated for the student.
 * @property-read Notifiable[] $notifications
 *
 * @method static \Database\Factories\StudentFactory factory($count = null, $state = [])
 * @method static Builder|Student newModelQuery()
 * @method static Builder|Student newQuery()
 * @method static Builder|Student query()
 * @method static Builder|Student where(string|\Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static Builder|Student whereId(int $value)
 * @method static Builder|Student whereLastName(string $value)
 * @method static Builder|Student whereFirstName(string $value)
 * @method static Builder|Student whereUsername(string $value)
 * @method static Builder|Student whereEmail(string $value)
 * @method static Builder|Student whereNull(string|array $columns, string $boolean = 'and', bool $not = false)
 * @method static Builder|Student whereNotNull(string|array $columns, string $boolean = 'and')
 * @method static Builder|Student whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static Builder|Student whereNotIn(string $column, mixed $values, string $boolean = 'and')
 * @method static Builder|Student whereDate(string $column, string $operator, mixed $value = null, string $boolean = 'and')
 * @method static Builder|Student whereYear(string $column, string $operator, mixed $value = null, string $boolean = 'and')
 * @method static Builder|Student whereMonth(string $column, string $operator, mixed $value = null, string $boolean = 'and')
 * @method static Builder|Student whereDay(string $column, string $operator, mixed $value = null, string $boolean = 'and')
 * @method static Builder|Student whereTime(string $column, string $operator, mixed $value = null, string $boolean = 'and')
 * @method static Builder|Student withTrashed()
 * @method static Builder|Student withoutTrashed()
 * @method static Builder|Student onlyTrashed()
 * @method static Builder|Student withFinancialsForYear(int $syear, ?int $schoolId) // Added for the new scope
 * @mixin \Eloquent
 */
class Student extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The authentication guard for students.
     *
     * @var string
     */
    protected string $guard_name = 'student';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'students';

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
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     * Fields like 'last_login', 'failed_login_attempts' should be managed programmatically, not through mass assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'last_name',
        'first_name',
        'middle_name',
        'name_suffix',
        'username',
        'password', // Handled by mutator or 'hashed' cast
        'email',
        'phone',
        'prem_number',
        'dob',
        'gender',
        'address',
        'custom_200000004',
        'custom_200000006',
        'custom_200000007',
        'custom_200000008',
        'custom_200000009',
        'custom_200000010',
        'custom_200000011',
    ];

    /**
     * The attributes that should be hidden for serialization (e.g., when converting to JSON).
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dob' => 'date:Y-m-d',
        'custom_200000004' => 'date:Y-m-d',
        'last_login' => 'datetime',
        'failed_login_attempts' => 'integer',
        'custom_200000010' => 'boolean',
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Set the student's password.
     * Handles hashing and explicitly sets to null if an empty value is provided.
     *
     * @param string|null $value
     * @return void
     */
    public function setPasswordAttribute(?string $value): void
    {
        if (!empty($value)) {
            $this->attributes['password'] = $value;
        } else {
            $this->attributes['password'] = null;
        }
    }

    /**
     * Get the student's username, ensuring it's lowercase.
     *
     * @param string|null $value
     * @return string|null
     */
    public function getUsernameAttribute(?string $value): ?string
    {
        return $value ? strtolower($value) : null;
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id', 'id');
    }

    public function currentActiveEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class, 'student_id', 'id')
            ->active()
            ->latest('start_date')
            ->latest('syear');
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class, 'student_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'student_id', 'id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'student_id', 'id');
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class, 'student_id', 'id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(Parents::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relationship')
            ->withTimestamps();
    }

    // =========================================================================
    // Accessors & Helper Methods
    // =========================================================================

    public function getFullNameAttribute(): string
    {
        return implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->name_suffix,
        ]));
    }

    public function getCurrentEnrollment(int $schoolId, int $syear): ?StudentEnrollment
    {
        return $this->enrollments()
            ->where('school_id', $schoolId)
            ->where('syear', $syear)
            ->active()
            ->first();
    }

    public function getEnrollmentRecord(int $schoolId, int $syear, ?int $gradeId = null): ?StudentEnrollment
    {
        $query = $this->enrollments()
            ->where('school_id', $schoolId)
            ->where('syear', $syear)
            ->active();

        if ($gradeId !== null) {
            $query->where('grade_id', $gradeId);
        }
        return $query->first();
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope a query to include students with financial activity (fees or payments)
     * for a specific year and optionally a specific school.
     *
     * @param Builder $query
     * @param int $syear The school year to filter financials by.
     * @param int|null $schoolId Optional: The ID of the school to filter financials by.
     * @return Builder
     */
    public function scopeWithFinancialsForYear(Builder $query, int $syear, ?int $schoolId): Builder
    {
        return $query->where(function (Builder $q) use ($syear, $schoolId) {
            $q->whereHas('fees', function (Builder $feeQuery) use ($syear, $schoolId) {
                $feeQuery->where('syear', $syear);
                if ($schoolId) {
                    $feeQuery->where('school_id', $schoolId);
                }
            })->orWhereHas('payments', function (Builder $paymentQuery) use ($syear, $schoolId) {
                $paymentQuery->where('syear', $syear);
                if ($schoolId) {
                    $paymentQuery->where('school_id', $schoolId);
                }
            });
        });
    }

    
    // Example: Scope a query to only include students with active enrollments in the current school year.
    public function scopeCurrentlyActive(Builder $query, ?int $syear = null, ?int $schoolId = null): Builder
    {
        $syear = $syear ?? Qs::getCurrentSchoolYear();
        $schoolId = $schoolId ?? Qs::getCurrentSchoolId();

        return $query->whereHas('enrollments', function (Builder $enrollmentQuery) use ($syear, $schoolId) {
            $enrollmentQuery->active()->where('syear', $syear);
            if ($schoolId) {
                $enrollmentQuery->where('school_id', $schoolId);
            }
        });
    }
    

    
    // Example: Scope a query to find students by a part of their name.
    public function scopeWhereNameLike(Builder $query, string $nameTerm): Builder
    {
        return $query->where(function(Builder $q) use ($nameTerm) {
            $q->where('first_name', 'LIKE', "%{$nameTerm}%")
              ->orWhere('last_name', 'LIKE', "%{$nameTerm}%")
              ->orWhere('middle_name', 'LIKE', "%{$nameTerm}%")
              ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$nameTerm}%");
        });
    }
    
}
