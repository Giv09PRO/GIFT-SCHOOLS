<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    // Specify the table name (optional, only if it doesn't follow Laravel's naming conventions)
    protected $table = 'user_profiles';

    // Specify the fields that can be mass assigned
    protected $fillable = [
        'profile',  // column name in the database
        'title',    // column name in the database
    ];

    // If you don't want to use the default created_at and updated_at columns, set this property to false
    public $timestamps = true;  // If you want the default created_at and updated_at timestamps
}
