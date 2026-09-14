<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication guard and password broker.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'staff'), // Changed to 'staff' as a more relevant default
        'passwords' => env('AUTH_PASSWORD_BROKER', 'staff'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Define the authentication guards for the application.
    |
    */

    'guards' => [
        'staff' => [
            'driver' => 'session',
            'provider' => 'staff',
        ],
        'students' => [
            'driver' => 'session',
            'provider' => 'students',
        ],
        'parents' => [
            'driver' => 'session',
            'provider' => 'parents',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Define the user providers for authentication.
    |
    */

    'providers' => [
        'staff' => [
            'driver' => 'eloquent',
            'model' => App\Models\Staff::class,
        ],
        'students' => [
            'driver' => 'eloquent',
            'model' => App\Models\Student::class,
        ],
        'parents' => [
            'driver' => 'eloquent',
            'model' => App\Models\Parents::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Define the password reset settings for each provider.
    |
    */

    'passwords' => [
        'staff' => [
            'provider' => 'staff',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'students' => [
            'provider' => 'students',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'parents' => [
            'provider' => 'parents',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | The duration (in seconds) before password confirmation times out.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
