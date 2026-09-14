<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory; // Recommended to add HasFactory
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable; // Common for Authenticatable models
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon; // For type hinting

/**
 * Represents a Parent user in the system.
 *
 * Note: Laravel convention typically uses singular model names (e.g., Parent).
 *
 * @method static Builder|Parents newModelQuery()
 * @method static Builder|Parents newQuery()
 * @method static Builder|Parents query()
 * @method static Builder|Parents where(string $column, mixed $value)
 * @method static Builder|Parents whereEmail(string $email)
 * @method static Builder|Parents whereId($value)
 * @method static Builder|Parents whereUsername(string $username)
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $middle_name
 * @property string|null $name_suffix
 * @property string $gender
 * @property string|null $name_prefix
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $username
 * @property string|null $password
 * @property Carbon|null $last_login
 * @property int|null $failed_login
 * @property Carbon|null $email_verified_at (If using email verification)
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection|Student[] $students
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 */
class Parents extends Authenticatable
{
    use HasFactory, Notifiable; // Added HasFactory and Notifiable traits

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'parents';

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
    protected $keyType = 'int'; // Or 'bigint' to match schema exactly

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'middle_name',
        'name_suffix',
        'gender',
        'name_prefix',
        'email',
        'phone',
        'username',
        'password',
        'last_login', // Be cautious making this fillable; usually updated programmatically
        'failed_login'  // Be cautious making this fillable; usually updated programmatically
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        // 'remember_token', // Standard for Authenticatable, add if you use it
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true; // Corresponds to created_at and updated_at

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_login' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'email_verified_at' => 'datetime', // Common Laravel field for email verification
        // For Laravel 9+, using the 'hashed' cast is recommended for passwords.
        // This makes the setPasswordAttribute mutator below redundant.
        // 'password' => 'hashed',
    ];

    /**
     * Define the many-to-many relationship between Parent and Student.
     * Parents can have multiple students, and students can have multiple parents.
     *
     * @return BelongsToMany
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'parent_student', 'parent_id', 'student_id')
            ->withPivot('relationship') // Include the 'relationship' field from the pivot table
            ->withTimestamps(); // If 'parent_student' pivot table has timestamps
    }

    /**
     * Set the password attribute.
     * If not using the 'hashed' cast (e.g., Laravel < 9 or specific needs),
     * this mutator ensures the password is hashed before saving.
     *
     * @param  string  $value
     * @return void
     */
    public function setPasswordAttribute(string $value): void
    {
        // If using the 'hashed' cast in $casts array (Laravel 9+), this mutator can be removed.
        // The 'hashed' cast handles this logic automatically and more robustly.

        // Hash password only if it's not already hashed according to the current app configuration.
        if (Hash::needsRehash($value)) {
            $this->attributes['password'] = Hash::make($value);
        } else {
            // If it doesn't need re-hashing, it means it's already hashed with the current algorithm
            // or it's a non-string value that Hash::needsRehash might return false for.
            // It's generally safer to just assign it if Hash::needsRehash is false,
            // assuming the input $value is intended to be the stored password if already "hashed".
            // However, if $value is plain text and somehow bypasses needsRehash, it would be stored as plain text.
            // This scenario is unlikely with typical password flows.
            // For maximum safety when this mutator is active, always hashing might be considered if plain text could arrive here:
            // $this->attributes['password'] = Hash::make($value);
            // But the needsRehash check is standard.
            $this->attributes['password'] = $value;
        }
    }

    /**
     * Get the username attribute.
     * Converts the username to lowercase.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getUsernameAttribute(?string $value): ?string
    {
        return $value ? strtolower($value) : null;
    }

    /**
     * Get the full name of the parent.
     * Accessor: $parent->full_name
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        $nameParts = [];
        if ($this->name_prefix) {
            $nameParts[] = $this->name_prefix;
        }
        $nameParts[] = $this->first_name;
        if ($this->middle_name) {
            $nameParts[] = $this->middle_name;
        }
        $nameParts[] = $this->last_name;
        if ($this->name_suffix) {
            $nameParts[] = $this->name_suffix;
        }
        return implode(' ', $nameParts);
    }

}
