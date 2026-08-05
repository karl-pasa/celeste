<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    | CELESTE uses bcrypt for account passwords. This is separate from the
    | SHA-256 fingerprints on certificates — bcrypt is deliberately slow and
    | salted, which is what you want for passwords and exactly what you do not
    | want for a document hash that must be reproducible on every verification.
    */

    'driver' => 'bcrypt',

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
        'limit'  => null,
    ],

    'argon' => [
        'memory'  => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time'    => env('ARGON_TIME', 4),
        'verify'  => true,
    ],

    'rehash_on_login' => true,
];
