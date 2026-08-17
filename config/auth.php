<?php

return [

    'defaults' => [
        'guard'     => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\User::class),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password reset
    |--------------------------------------------------------------------------
    | 'expire' is the token lifetime in minutes. The framework default is 60;
    | 15 is used here because a reset link is a bearer credential sitting in an
    | inbox, and this system's users read mail on shared campus machines.
    |
    | 'throttle' is the minimum seconds between reset requests for one account,
    | which stops the endpoint being used to flood a mailbox.
    |
    | Tokens are stored hashed by Laravel's broker, so a database read does not
    | yield usable links.
    */
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 15,
            'throttle' => 60,
        ],
    ],

    /*
    | How long a password confirmation stays valid before a sensitive action
    | asks for it again. One hour.
    */
    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 3600),
];
