<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | Use 'main' for frontend hashed IDs in the school management system.
    |
    */

    'default' => 'main',

    /*
    |--------------------------------------------------------------------------
    | Hashids Connections
    |--------------------------------------------------------------------------
    |
    | Configure 'main' for obfuscating student IDs in frontend URLs and datatables.
    | 'alternative' is optional for future use (e.g., public-facing IDs).
    |
    */

    'connections' => [

        'main' => [
            'salt' => env('HASHIDS_SALT', 'your-secure-random-salt-for-school-system-2025'),
            'length' => 8, // 8-character hashes for frontend (e.g., aBcd1234)
            'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', // Default for maximum entropy
        ],

        'alternative' => [
            'salt' => env('HASHIDS_ALTERNATIVE_SALT', 'alternative-secure-salt-2025'),
            'length' => 12, // Longer for potential public use
            'alphabet' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890',
        ],

    ],

];
